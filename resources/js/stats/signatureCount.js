const getData = async () => {
    const response = await fetch('/api/stats/signatures/count');
    return await response.json();
}

const updateCount = async () => {
    const count = await getData();
    const totalElement = document.getElementById('count-total');
    const todayElement = document.getElementById('count-24h');
    const thirthyMinutesElement = document.getElementById('count-30m');

    totalElement.textContent = String(count.total).padStart(6, '0');
    todayElement.textContent = String(count.today).padStart(4, '0');
    thirthyMinutesElement.textContent = String(count.thirtyMinutes).padStart(4, '0');
}

setInterval(updateCount, 1000);
