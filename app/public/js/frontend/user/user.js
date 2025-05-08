import { UserClass } from './userClass.js';

const userClass = new UserClass();

document.addEventListener('DOMContentLoaded', function () {
    // Références aux éléments
    const gender = document.getElementById('user_gender');
    const situation = document.getElementById('user_situation');

    userClass.getSituations(gender, situation);
});