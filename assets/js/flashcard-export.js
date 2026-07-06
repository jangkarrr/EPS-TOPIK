/**
 * Flashcard Export Utilities
 * Handles CSV and XLSX export using SheetJS
 */
const FlashcardExport = {

    /**
     * Export cards to CSV and trigger download
     */
    toCSV(cards, filename = 'flashcards') {
        const headers = ['Term', 'Definition', 'Image'];
        const rows = cards.map(c => [
            this._escapeCSV(c.term || c.Term || ''),
            this._escapeCSV(c.definition || c.Definition || ''),
            this._escapeCSV(c.image_path || c.Image || '')
        ]);

        let csv = headers.join(',') + '\n';
        csv += rows.map(r => r.join(',')).join('\n');

        const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
        this._download(blob, `${filename}.csv`);
    },

    /**
     * Export cards to XLSX using SheetJS
     */
    toXLSX(cards, filename = 'flashcards') {
        if (typeof XLSX === 'undefined') {
            showToast('Excel library not loaded. Please try again.', 'error');
            return;
        }

        const data = cards.map(c => ({
            Term: c.term || c.Term || '',
            Definition: c.definition || c.Definition || '',
            Image: c.image_path || c.Image || ''
        }));

        const ws = XLSX.utils.json_to_sheet(data);
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Flashcards');

        // Column widths
        ws['!cols'] = [
            { wch: 30 },
            { wch: 50 },
            { wch: 30 }
        ];

        XLSX.writeFile(wb, `${filename}.xlsx`);
    },

    /**
     * Parse CSV text into array of objects
     */
    parseCSV(text) {
        const lines = text.split(/\r?\n/).filter(l => l.trim());
        if (lines.length < 2) return { headers: [], rows: [] };

        const headers = this._parseCSVLine(lines[0]);
        const rows = [];

        for (let i = 1; i < lines.length; i++) {
            const values = this._parseCSVLine(lines[i]);
            if (values.some(v => v.trim())) {
                const row = {};
                headers.forEach((h, idx) => {
                    row[h] = values[idx] || '';
                });
                rows.push(row);
            }
        }

        return { headers, rows };
    },

    /**
     * Parse a single CSV line handling quoted fields
     */
    _parseCSVLine(line) {
        const result = [];
        let current = '';
        let inQuotes = false;

        for (let i = 0; i < line.length; i++) {
            const ch = line[i];
            if (inQuotes) {
                if (ch === '"') {
                    if (i + 1 < line.length && line[i + 1] === '"') {
                        current += '"';
                        i++;
                    } else {
                        inQuotes = false;
                    }
                } else {
                    current += ch;
                }
            } else {
                if (ch === '"') {
                    inQuotes = true;
                } else if (ch === ',') {
                    result.push(current.trim());
                    current = '';
                } else {
                    current += ch;
                }
            }
        }
        result.push(current.trim());
        return result;
    },

    /**
     * Escape value for CSV
     */
    _escapeCSV(val) {
        val = String(val);
        if (val.includes(',') || val.includes('"') || val.includes('\n')) {
            return '"' + val.replace(/"/g, '""') + '"';
        }
        return val;
    },

    /**
     * Trigger file download
     */
    _download(blob, filename) {
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(url);
    }
};
