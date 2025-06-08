"use strict";

import { TableClass } from './tableClass.js';

const tableClass = new TableClass();

// Trie les colonnes d'une table
document.addEventListener('DOMContentLoaded', function () {
    tableClass.sortTable();
});