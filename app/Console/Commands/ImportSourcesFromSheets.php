<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Sheet;
use App\Models\Counting;

class ImportSourcesFromSheets extends Command
{
    const SIGNATURE_COLLECTION_ID = 2;
    const LOG_INTERVAL = 1000;
    const DEFAULT_SOURCE_ID = 71; // unknown source

    protected $signature = 'import:sources-from-sheets
        {--dry-run : Do not persist changes}
        {--file-name= : The csv file to import from}';

    protected $description = 'Migrate counts by source from Sheets to Countings. This is not possible exactly.';

    public function handle(): int
    {
        $dry = (bool) $this->option('dry-run');
        $fileName = $this->option('file-name');

        // stats vars
        $countings_duplicated = 0;
        $rows_duplicated = 0;
        $countings_added = 0;
        $row_counting_matches = 0;
        $rows_missing_sources = 0;
        $total_signatures_in_csv = 0;

        // open the csv file or throw an error
        if (!file_exists($fileName)) {
            $this->error("File $fileName not found.");
            return 1;
        }
        $file = fopen($fileName, 'r');
        if ($file === false) {
            $this->error("Could not open file $fileName.");
            return 1;
        }
        $header = fgetcsv($file);
        if ($header === false) {
            $this->error("Could not read header from file $fileName.");
            return 1;
        }
        $rows = [];
        while (($data = fgetcsv($file)) !== false) {
            $rows[] = array_combine($header, $data);
        }
        fclose($file);

        if (Counting::where('signature_collection_id', self::SIGNATURE_COLLECTION_ID)->whereNotNull('source_id')->exists()) {
            $this->error("Some Countings already have a source assigned. Aborting.");
            return 1;
        }

        // cast rows count and source_id to int
        foreach ($rows as &$row) {
            $row['count'] = (int) $row['count'];
            $total_signatures_in_csv += $row['count'];
            if (!is_numeric($row['source_id'])) {
                $row['source_id'] = self::DEFAULT_SOURCE_ID; // unknown source, manually choose a default
                $rows_missing_sources++;
            } else {
              $row['source_id'] = (int) $row['source_id'];
            }
        }
        unset($row);

        // print source stats about the rows, sum how many signatures per source_id
        $source_stats = [];
        foreach ($rows as $row) {
            if (!isset($source_stats[$row['source_id']])) {
                $source_stats[$row['source_id']] = 0;
            }
            $source_stats[$row['source_id']] += $row['count'];
        }
        $this->info("Source stats from CSV:");
        foreach ($source_stats as $source_id => $count) {
            $this->info("Source ID $source_id: $count signatures");
        }

        // iterate over Countings in system and rows in parallel
        // if the current counting has more signatures than the row:
        //  duplicate it, setting the first count to the row count and the second to the rest. set the second counting as current counting and get a new row.
        // if the current counting has less signatures than the row, duplicate the row, setting the first count to the counting count and the second to the rest. set the first counting's source to the rows source. set the second row as current row and get a new counting.
        // if the current counting has the same number of signatures as the row, set the counting's source to the row's source and get a new counting and a new row.

        $countings = Counting::where('signature_collection_id', self::SIGNATURE_COLLECTION_ID)->orderBy('date')->get();
        $rowIndex = 0;
        foreach ($countings as $counting) {
            if ($rowIndex >= count($rows)) {
              // no more rows to process
              // this means there are more signatures in countings than in rows
              // that's ok, there is nothing more to do
              break;
            }

            while ($counting->count > $rows[$rowIndex]['count']) {
                // new counting that can be finished now together with the current row
                if ($rowIndex >= count($rows)) {
                  throw new \Exception("Logic error: rowIndex exceeded rows count inside while loop.");
                }
                $newCounting = $counting->replicate();
                $newCounting->count = $rows[$rowIndex]['count'];
                $newCounting->source_id = $rows[$rowIndex]['source_id'];
                $countings_duplicated++;
                if ($countings_duplicated % self::LOG_INTERVAL == 0) {
                    $this->info("Duplicated $countings_duplicated countings so far...");
                }
                if (!$dry) {
                    $newCounting->save();
                }
                $counting->count -= $rows[$rowIndex]['count'];
                if (!$dry) {
                    $counting->save();
                }
                $rowIndex++;
                if($rowIndex >= count($rows)) {
                  // all rows processed
                  break;
                }
            }
            // if we've exhausted rows while inside the inner loop, stop processing
            if ($rowIndex >= count($rows)) {
                    break;
            }

            if ($counting->count == $rows[$rowIndex]['count']) {
              // finish both and continue
              $counting->source_id = $rows[$rowIndex]['source_id'];
              if (!$dry) {
                  $counting->save();
              }
              $rowIndex++;

              $row_counting_matches++;
              if ($row_counting_matches % self::LOG_INTERVAL == 0) {
                  $this->info("Matched $row_counting_matches rows and countings so far...");
              }
              if($rowIndex >= count($rows)) {
                // all rows processed
                break;
              }
              continue;
            }
            
            if ($counting->count < $rows[$rowIndex]['count']) {
              // counting can be finished now, row is modified to remaining count
              $newRowCount = $rows[$rowIndex]['count'] - $counting->count;
              $counting->source_id = $rows[$rowIndex]['source_id'];
              if (!$dry) {
                  $counting->save();
              }
              $rows[$rowIndex]['count'] = $newRowCount;
              $rows_duplicated++;
              if ($rows_duplicated % self::LOG_INTERVAL == 0) {
                  $this->info("Duplicated $rows_duplicated rows so far...");
              }
            } 
        }
        // get the date of the last counting
        $lastCountingDate = Counting::where('signature_collection_id', self::SIGNATURE_COLLECTION_ID)->whereNotNull('date')->orderBy('date', 'desc')->first()->date;

        // for each remaining row, create a new counting with the last counting date
        while ($rowIndex < count($rows)) {
          // there are more signatures in rows than in countings
          // add a counting for each remaining row
          $newCounting = new Counting();
          $newCounting->signature_collection_id = self::SIGNATURE_COLLECTION_ID;
          $newCounting->count = $rows[$rowIndex]['count'];
          $newCounting->source_id = $rows[$rowIndex]['source_id'];
          $newCounting->date = $lastCountingDate;
          if (!$dry) {
              $newCounting->save();
          }
          $rowIndex++;
          $countings_added++;
          if ($countings_added % self::LOG_INTERVAL == 0) {
              $this->info("Added $countings_added new countings so far...");
          }
        }

        $this->info("Import completed.");
        $this->info("Total signatures in csv: $total_signatures_in_csv");
        $this->info("Rows matched to countings: $row_counting_matches");
        $this->info("Rows with missing sources: $rows_missing_sources");
        $this->info("Countings duplicated: $countings_duplicated");
        $this->info("Rows duplicated: $rows_duplicated");
        $this->info("New Countings added: $countings_added on date $lastCountingDate");
        return 0;
    }
}
