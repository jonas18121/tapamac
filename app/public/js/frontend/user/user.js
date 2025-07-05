"use strict";

import { UserClass } from './userClass.js';

const userClass = new UserClass();

// Permet d'obtenir une liste de situation dans un champ de selection
document.addEventListener('DOMContentLoaded', function () {
    // Références aux éléments
    const gender = document.getElementById('user_gender');
    const situation = document.getElementById('user_situation');

    userClass.getSituations(gender, situation);
});

// Permet d'obtenir une liste de Type de contrat dans un champ de selection
document.addEventListener('DOMContentLoaded', function () {
    // Références aux éléments
    const professional = document.getElementById('user_professional');
    const typeOfContract = document.getElementById('user_typeOfContract');

    userClass.getTypeOfContract(professional, typeOfContract);
});
