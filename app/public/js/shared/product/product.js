"use strict";

import { ProductClass } from './productClass.js';

const productClass = new ProductClass();

// Remplace l'image par défaut par l'image qui a été cliqué
document.addEventListener('DOMContentLoaded', function () {
    productClass.changeImageDetail() 
});