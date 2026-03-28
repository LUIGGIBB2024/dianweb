const puppeteer = require('puppeteer');
const fs = require('fs');
const path = require('path');

// carpeta LOCAL (NO usar Windows Temp)
const userDataDir = path.join(__dirname, 'chrome-profile');

// 🔥 eliminar carpeta si existe (borra locks)
if (fs.existsSync(userDataDir)) {
    fs.rmSync(userDataDir, { recursive: true, force: true });
}

(async () => {

    const url = process.argv[2];

    console.log("🚀 Lanzando navegador limpio...");

    const browser = await puppeteer.launch({
        headless: false,
        userDataDir: userDataDir, // 👈 CONTROL TOTAL
        args: [
            '--no-sandbox',
            '--disable-setuid-sandbox',
            '--disable-dev-shm-usage'
        ]
    });

    try {

        const page = await browser.newPage();

        await page.goto(url, { waitUntil: 'networkidle2' });

        await page.waitForTimeout(5000);

        const button = await page.$('#DocumentReceived a');

        if (!button) {
            console.log("❌ Botón no encontrado");
            return;
        }

        console.log("✅ Click...");

        await Promise.all([
            page.waitForNavigation({ waitUntil: 'networkidle2' }).catch(() => {}),
            button.click()
        ]);

        await page.waitForTimeout(8000);

        console.log("🌐 URL actual:", page.url());

        const html = await page.content();

        console.log(html);

    } catch (e) {
        console.error("❌ Error:", e);
    }

    await browser.close();

})();
