"use strict";

export class UserClass {
    
    constructor() {
        
    }

    /**
     * Permet d'obtenir une liste de situation dans un champ de selection
     * 
     * @param {String} gender 
     * @param {String} situation 
     * 
     * @returns {void}
     */
    getSituations(gender, situation) 
    {
        // Écouteur sur le select gender
        gender.addEventListener('change', function() {
            const selectedGender = this.value;   
            
            // Si un genre est sélectionnée, on continu dans le if
            if (selectedGender) {
                // On vide d'abord l'ancien contenu
                situation.innerHTML = '';

                // Ajax appel le controler
                fetch(`/user/ajax/get/situations?gender=${selectedGender}`)
                .then(response => response.json())
                .then(data => {
                    Object.entries(data).forEach(([label, value]) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = label;
                        situation.appendChild(option); // Fournit les options dans le HTML
                    });
                });
            } 
        });
    }

    /**
     * Permet d'obtenir une liste de type de contrat dans un champ de selection
     * 
     * @param {String} professional 
     * 
     * @returns {void}
     */
    getTypeOfContract(professional) 
    {
        const EMPLOYE = 'employe';

        // Écouteur sur le select professional
        professional.addEventListener('change', function() {
            const selectedProfessional = this.value;   
            const containerTypeOfContract = document.getElementById('container_user_typeOfContract');

            // Réinitialise le contenu du container
            containerTypeOfContract.innerHTML = '';

            // Si employe est sélectionnée en valeur de Situation professionnel, on continu dans le if
            if (selectedProfessional && EMPLOYE === selectedProfessional) {
                // Création du label
                const label = document.createElement("label");
                label.setAttribute("for", "user_typeOfContract");
                label.classList.add("form_label");
                label.textContent = "Type de contrat";

                // Création du select
                const typeOfContract = document.createElement("select");
                typeOfContract.name = "user[typeOfContract]";
                typeOfContract.id = "user_typeOfContract";
                typeOfContract.classList.add("form_input");

                // Ajax appel le controler
                fetch(`/user/ajax/get/typeOfContract?professional=${selectedProfessional}`)
                .then(response => response.json())
                .then(data => {
                    Object.entries(data).forEach(([label, value]) => {
                        const option = document.createElement('option');
                        option.value = value;
                        option.textContent = label;
                        typeOfContract.appendChild(option); // Fournit les options dans le HTML
                    });
                });

                // Insertion dans le DOM
                containerTypeOfContract.append(label);
                containerTypeOfContract.appendChild(typeOfContract); 
            } 
        });
    }
}
