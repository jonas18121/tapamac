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
}