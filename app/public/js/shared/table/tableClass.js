"use strict";

export class TableClass {
    
    constructor() {
        
    }

    /**
     * Trie les colonnes d'une table
     * 
     * @returns {void}
     */
    sortTable() {
        const table = document.querySelector('.table');
        const headers = table.querySelectorAll('th');
        const tbody = table.querySelector('tbody');

        headers.forEach((header, index) => {
            if (header.textContent === 'Action') return; // Ignore colonne Action

            header.style.cursor = 'pointer';
            header.addEventListener('click', () => {
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const isAscending = header.classList.contains('asc');

                // Supprime les classes CSS asc et desc de tous les <th> (en-têtes) de la table.
                headers.forEach(th => th.classList.remove('asc', 'desc'));

                header.classList.toggle('asc', !isAscending);
                header.classList.toggle('desc', isAscending);              

                const type = this.detectType(this.getCellValue(rows[0], index));

                // Trie les lignes du <tbody> de la table en fonction de la colonne cliquée (<th>).
                rows.sort((rowA, rowB) => {      
                    const rowAVal = this.parseValue(this.getCellValue(rowA, index), type);
                    const rowBVal = this.parseValue(this.getCellValue(rowB, index), type);

                    if (rowAVal > rowBVal) {
                        if (isAscending) {
                            // Tri décroissant, mettre rowAVal avant rowBVal ⇒ on renvoie -1.
                            return -1;
                        } 
                        else {
                            // Tri croissant, mettre rowAVal après rowBVal ⇒ on renvoie 1
                            return 1;
                        }
                    }

                    if (rowAVal < rowBVal) {
                        if (isAscending) {
                            // Tri croissant, mettre rowAVal après rowBVal ⇒ on renvoie 1.
                            return 1;
                        } 
                        else {
                            // Tri décroissant, mettre rowAVal avant rowBVal ⇒ on renvoie -1
                            return -1;
                        }
                    }

                    return 0;
                });

                rows.forEach(row => tbody.appendChild(row));
            });
        });
    }

    /**
     * Obtenir la valeur d'une cellule
     * 
     * @param {Object} row 
     * @param {int} index 
     * 
     * @returns {string} 
     */
    getCellValue(row, index) {
        return row.children[index].innerText.trim();
    }

    /**
     * Détecter le type de données
     * 
     * @param {string} value 
     * 
     * @returns {string} 
     */
    detectType(value) {
        if (/^\d{2}\/\d{2}\/\d{4}( \d{2}:\d{2})?$/.test(value)) {
            return 'date';
        }

        if (!isNaN(value.replace(',', '.'))) {
            return 'number';
        }
        
        return 'string';
    }

    // Parser une valeur en fonction de son type
    /**
     * Détecter le type de données
     * 
     * @param {string} value 
     * @param {string} type 
     * 
     * @returns {Date|string|float} 
     */
    parseValue(value, type) {
        if (type === 'date') {
            const parts = value.split(/[\s/:]/); // [dd, mm, yyyy, hh, mm]
            return new Date(parts[2], parts[1] - 1, parts[0], parts[3] || 0, parts[4] || 0).getTime();
        }

        if (type === 'number') {
            return parseFloat(value.replace(',', '.'));
        } 

        // si c'est un string par défaut
        return value.toLowerCase();
    }
}