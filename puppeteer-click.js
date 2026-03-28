const puppeteer = require('puppeteer-core');

(async () => {
    const url = process.argv[2]; // recibe la URL como argumento

    const browser = await puppeteer.launch({
        executablePath: 'C:\\Program Files\\Google\\Chrome\\Application\\chrome.exe', // ajusta tu ruta de Chrome
        headless: true,
        args: ['--no-sandbox', '--disable-web-security', '--disable-setuid-sandbox']
    });

    const page = await browser.newPage();

    await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');

    // 1️⃣ Navegar y esperar que la redirección del token termine
    await page.goto(url, { waitUntil: 'networkidle0', timeout: 60000 });

    // 2️⃣ Esperar que #DocumentReceived aparezca en el DOM
    try {
        await page.waitForSelector('#DocumentReceived a', { timeout: 15000 });
    } catch (e) {
        const htmlError = await page.content();
        console.log(JSON.stringify({
            error: 'No se encontró #DocumentReceived',
            preview: htmlError.substring(0, 3000)
        }));
        await browser.close();
        process.exit(1);
    }

    // 3️⃣ Hacer clic real sobre el enlace
    await page.click('#DocumentReceived a');

    // 4️⃣ Esperar que la tabla cargue tras el clic
    try {
        await page.waitForSelector('table', { timeout: 20000 });
    } catch (e) {
        // Si no aparece tabla, igual devolvemos el HTML para diagnóstico
    }

    // 5️⃣ Esperar un poco más por si hay AJAX
    await page.waitForTimeout(5000);

    const html = await page.content();

    console.log(JSON.stringify({
        tiene_tabla: html.includes('<table'),
        tiene_document_received: html.includes('DocumentReceived'),
        preview: html.substring(0, 100000000)
    }));

    await browser.close();
})();
