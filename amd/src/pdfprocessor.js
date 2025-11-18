// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * TODO describe module pdfprocessor
 *
 * @module     aiplacement_contentgenerator/pdfprocessor
 * @copyright  2025 Jan Rieger <jan.rieger@th-luebeck.de>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import * as pdfjsLib from './pdfjs/pdf.mjs';

export const init = () => {
    // Worker-Pfad setzen
    pdfjsLib.GlobalWorkerOptions.workerSrc = M.cfg.wwwroot + '/ai/placement/contentgenerator/amd/src/pdfjs/pdf.worker.mjs';

    const form = document.querySelector('#generatepdfform'); // ID deines Moodle-Formulars anpassen
    if (!form) return;

    // Ladebalken erstellen
    const progressContainer = document.createElement('div');
    progressContainer.style.width = '100%';
    progressContainer.style.border = '1px solid #ccc';
    progressContainer.style.margin = '10px 0';
    const progressBar = document.createElement('div');
    progressBar.style.width = '0%';
    progressBar.style.height = '20px';
    progressBar.style.backgroundColor = '#4caf50';
    progressContainer.appendChild(progressBar);
    form.append(progressContainer);
    // add text to progress container
    const progressText = document.createElement('div');
    progressText.style.textAlign = 'center';
    progressText.style.marginTop = '-20px';
    progressText.style.color = '#000000ff';
    progressText.innerText = 'Process PDF pages...';
    progressContainer.appendChild(progressText);
    // hide progress bar initially
    progressContainer.style.display = 'none';

    form.addEventListener('submit', async e => {
        e.preventDefault(); // Absenden stoppen
        // show progress bar
        progressContainer.style.display = 'block';
        const checkboxes = Array.from(form.querySelectorAll('input[type="checkbox"][name^="mod_"]:checked'));
        const results = {};
        let totalPages = 0;

        // Gesamtseiten zählen für Fortschritt
        for (const box of checkboxes) {
            const mimetype = box.dataset.mimetype;
            if (mimetype !== 'application/pdf') {
                continue; // Nur PDFs zählen
            }
            const url = box.dataset.url;
            const pdf = await pdfjsLib.getDocument(url).promise;
            totalPages += pdf.numPages;
        }

        let processedPages = 0;

        for (const box of checkboxes) {
            const mimetype = box.dataset.mimetype;
            if (mimetype !== 'application/pdf') {
                continue; // Nur PDFs verarbeiten
            }
            const fileid = box.name.split('_').pop();
            const url = box.dataset.url;
            const pdf = await pdfjsLib.getDocument(url).promise;

            results[fileid] = [];

            for (let i = 1; i <= pdf.numPages; i++) {
                const page = await pdf.getPage(i);
                const viewport = page.getViewport({ scale: 1.5 });
                const canvas = document.createElement('canvas');
                const ctx = canvas.getContext('2d');
                canvas.width = viewport.width;
                canvas.height = viewport.height;
                await page.render({ canvasContext: ctx, viewport }).promise;
                const img = canvas.toDataURL('image/png');
                results[fileid].push(img);

                // Fortschritt aktualisieren
                processedPages++;
                const percent = Math.round((processedPages / totalPages) * 100);
                progressBar.style.width = percent + '%';
            }
        }

        document.querySelector('input[name="pdfimages"]').value = JSON.stringify(results);

        form.submit(); // Formular jetzt absenden
    });
};
