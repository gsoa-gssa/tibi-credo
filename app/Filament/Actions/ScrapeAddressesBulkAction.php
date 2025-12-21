<?php

namespace App\Filament\Actions;

use App\Models\Commune;
use App\Models\Zipcode;
use Filament\Notifications\Notification;
use Filament\Tables\Actions\BulkAction;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class ScrapeAddressesBulkAction extends BulkAction
{
    public static function getDefaultName(): ?string
    {
        return 'scrape_addresses_bulk';
    }

    protected function isPoBoxToken(string $s): bool
    {
        $t = mb_strtolower(trim($s));
        // Remove dots and condense spaces for matching abbreviations
        $t = str_replace(['.', '‐', '‑', '‒', '–', '—'], '', $t);
        $t = preg_replace('/\s+/', ' ', $t);

        // Common DE/FR/IT PO box terms and abbreviations
        $patterns = [
            'postfach',            // DE
            'po box', 'p o box',   // EN/variant
            'boite postale', 'boîte postale', // FR
            'case postale', 'c p', 'cp',      // CH-FR
            'casella postale',                 // IT
            'bp',                  // FR abbreviation (Boîte Postale)
        ];

        foreach ($patterns as $p) {
            if (str_starts_with($t, $p)) {
                return true;
            }
        }

        // Also match tokens like "Postfach 123", "CP 45", "BP123"
        if (preg_match('/^(postfach|cp|bp|pf)\s*\d+/u', $t)) {
            return true;
        }

        if (preg_match('/^(po\s*box)\s*\d*/u', $t)) {
            return true;
        }

        return false;
    }

    protected function extractAddress(string $html, Commune $commune): array
    {
        $body = html_entity_decode($html, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = strip_tags($body);
        $normalized = preg_replace('/\r\n|\r/', "\n", $text);
        $normalized = preg_replace('/\n{2,}/', "\n", $normalized);
        $lines = array_values(array_filter(array_map('trim', explode("\n", $normalized)), fn($l) => $l !== ''));

        // Collect all candidates
        $candidates = [];
        for ($i = 0; $i < count($lines); $i++) {
            $line = $lines[$i];
            if (preg_match('/\b(?:CH\s*[-‐‑‒–—]?\s*)?(\d{4})\s+([A-Za-zÀ-ÖØ-öø-ÿ\'\'`\.\-\s]+)\b/iu', $line, $pm, PREG_OFFSET_CAPTURE)) {
                $postcode = trim($pm[1][0]);
                $place = trim($pm[2][0]);
                $street = null;
                $house = null;

                // Try to extract street/house from same line before the postcode
                $pre = trim(substr($line, 0, max(0, $pm[0][1])));
                if ($pre !== '') {
                    // Split on commas/semicolons/pipes to isolate tokens
                    $tokens = preg_split('/[\,;\|]/u', $pre) ?: [];
                    // Walk backwards to find the last non-PO-box token
                    for ($t = count($tokens) - 1; $t >= 0; $t--) {
                        $tok = trim($tokens[$t]);
                        if ($tok === '' || $this->isPoBoxToken($tok)) {
                            continue;
                        }
                        if (preg_match('/^([A-Za-zÀ-ÖØ-öø-ÿ\'\'`\.\-\s]+?)\s+(\d+[A-Za-z]?)/u', $tok, $sm)) {
                            $street = trim($sm[1]);
                            $house = trim($sm[2]);
                        } else {
                            // Token might be only street without number
                            $street = rtrim($tok, ', ');
                        }
                        break;
                    }
                }

                // Fallback: look at previous non-PO-box lines
                if ($street === null) {
                    for ($b = $i - 1; $b >= max(0, $i - 3); $b--) {
                        $candidateLine = trim($lines[$b]);
                        if ($candidateLine === '' || $this->isPoBoxToken($candidateLine)) {
                            continue;
                        }
                        if (preg_match('/^([A-Za-zÀ-ÖØ-öø-ÿ\'\'`\.\-\s]+?)\s+(\d+[A-Za-z]?)/u', $candidateLine, $sm)) {
                            $street = trim($sm[1]);
                            $house = trim($sm[2]);
                        } else {
                            $street = rtrim($candidateLine, ', ');
                        }
                        break;
                    }
                }

                $raw = trim(($pre !== '' ? $pre : ($lines[$i - 1] ?? '')) . ' | ' . $line);
                $candidates[] = [
                    'street' => $street,
                    'house' => $house,
                    'postcode' => $postcode,
                    'place' => $place,
                    'raw' => $raw,
                ];
            }
        }

        if (empty($candidates)) {
            return [null, null, null, null, null];
        }

        // Get all matching postcodes from database based on numeric check
        $postcodes = array_unique(array_column($candidates, 'postcode'));
        $validZipcodes = Zipcode::whereIn('code', $postcodes)->get();

        if ($validZipcodes->isEmpty()) {
            return [null, null, null, null, null];
        }

        // Restrict to candidates whose (postcode, place) pair matches a Zipcode entry
        $namesByCode = $validZipcodes
            ->groupBy('code')
            ->map(fn ($group) => $group->pluck('name')->map(fn ($n) => trim(mb_strtolower($n)))->unique()->values()->all())
            ->toArray();

        $normalize = fn (string $s) => trim(mb_strtolower($s));
        $pairMatched = array_filter($candidates, function ($c) use ($namesByCode, $normalize) {
            $code = $c['postcode'];
            $place = $normalize($c['place'] ?? '');
            if (!isset($namesByCode[$code])) {
                return false;
            }
            foreach ($namesByCode[$code] as $known) {
                if ($place === $known) {
                    return true;
                }
                if ($place !== '' && levenshtein($place, $known) < 3) {
                    return true;
                }
            }
            return false;
        });

        if (empty($pairMatched)) {
            // If no (code, name) pairs match (even fuzzily), there are no valid candidates
            return [null, null, null, null, null];
        }

        $validCandidates = $pairMatched;

        // prefer zipcodes assigned to this commune (still respecting (code, name) pairing)
        $communeZipcodes = $commune->zipcodes()->pluck('code')->toArray();
        $communeCandidates = array_filter($validCandidates, fn ($c) => in_array($c['postcode'], $communeZipcodes));
        
        if (!empty($communeCandidates)) {
            $validCandidates = $communeCandidates;
        }

        // Return the first valid candidate
        $best = reset($validCandidates);
        return [$best['street'], $best['house'], $best['postcode'], $best['place'], $best['raw']];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('commune.scrape_addresses'))
            ->icon('heroicon-o-globe-alt')
            ->color('gray')
            ->action(function (Collection $records) {
                try {
                    $valid = $records->filter(fn (Commune $c) => $c->address_checked === false && $c->website);

                    if ($valid->isEmpty()) {
                        Notification::make()
                            ->warning()
                            ->title(__('commune.scrape_addresses_none'))
                            ->send();
                        return;
                    }

                    $handle = fopen('php://temp', 'r+');
                    fputcsv($handle, ['Kunden-ID', 'Authority', 'Website', 'Strasse', 'Hausnummer', 'PLZ', 'Ort', 'Treffer Rohtext']);

                    $total = $valid->count();
                    $success = 0;
                    $noAddress = 0;
                    $failed = 0;

                    foreach ($valid as $commune) {
                        $url = $commune->website;
                        $authority = $commune->address ? trim(strtok(strip_tags(str_replace('<br>', "\n", $commune->address)), "\n")) : $commune->name;
                        [$street, $houseNumber, $postcode, $place, $raw] = [null, null, null, null, null];

                        try {
                            $response = Http::timeout(1)->get($url);
                            if ($response->successful()) {
                                [$street, $houseNumber, $postcode, $place, $raw] = $this->extractAddress($response->body(), $commune);
                                
                                if ($street || $postcode) {
                                    fputcsv($handle, [
                                        $commune->id,
                                        $authority,
                                        $url,
                                        $street,
                                        $houseNumber,
                                        $postcode,
                                        $place,
                                        $raw,
                                    ]);
                                    $success++;
                                } else {
                                    $noAddress++;
                                }
                            } else {
                                $failed++;
                            }
                        } catch (Throwable $e) {
                            $failed++;
                        }
                    }

                    rewind($handle);

                    Notification::make()
                        ->success()
                        ->title(__('commune.scrape_addresses_complete'))
                        ->body(__('commune.scrape_addresses_stats', [
                            'total' => $total,
                            'success' => $success,
                            'noAddress' => $noAddress,
                            'failed' => $failed,
                        ]))
                        ->send();

                    return response()->streamDownload(function () use ($handle) {
                        fpassthru($handle);
                    }, 'scraped-addresses.csv', [
                        'Content-Type' => 'text/csv',
                    ]);
                } catch (Throwable $e) {
                    Notification::make()
                        ->danger()
                        ->title(__('commune.scrape_addresses_failed'))
                        ->body($e->getMessage())
                        ->send();
                }
            });
    }
}
