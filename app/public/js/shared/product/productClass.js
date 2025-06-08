"use strict";

export class ProductClass {
    
    constructor() {
        
    }

    /**
     * Permet de remplacer l'image par défaut par l'image qui a été selectionné
     * 
     * @returns {void}
     */
    changeImageDetail() 
    {
        const imageDefault = document.getElementById('image_default');    
        // préfixe
        const prefix = "image_";

        document.addEventListener('click', function (event) {
            const target = event.target;        

            // Vérifie que l'élément cliqué est un <img> avec un id qui commence par le préfixe
            if (target.tagName === 'IMG' && target.id.startsWith(prefix)) {
                event.preventDefault();

                // Récupère l'attribut src de l'image cliquée
                const newSrc = target.getAttribute('src');
                // Remplace le src de l'image par défaut
                imageDefault.setAttribute('src', newSrc);

                // Récupère l'attribut alt de l'image cliquée
                const newAlt = target.getAttribute('alt');
                // Remplace le alt de l'image par défaut
                imageDefault.setAttribute('alt', newAlt);
            }
        });
    }
}