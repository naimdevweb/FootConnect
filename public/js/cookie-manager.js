/**
 * Gestionnaire de cookies pour FootConnect
 * Permet de gérer les préférences utilisateur en matière de cookies
 */
class CookieManager {
    constructor() {
        this.preferences = {
            essential: true, // Toujours nécessaires
            functional: this.getPreference('functional', true),
            analytics: this.getPreference('analytics', true)
        };
        
        this.hasConsented = localStorage.getItem('cookieChoice') !== null;
        this.init();
    }
    
    /**
     * Initialisation du gestionnaire
     */
    init() {
        document.addEventListener('DOMContentLoaded', () => {
            this.setupBanner();
            this.setupModal();
        });
    }
    
    /**
     * Configuration de la bannière de cookies
     */
    setupBanner() {
        const cookieBanner = document.getElementById('cookie-banner');
        const cookieAccept = document.getElementById('cookie-accept');
        const cookieCustomize = document.getElementById('cookie-customize');
        const cookieRefuse = document.getElementById('cookie-refuse');
        
        if (!cookieBanner) return;
        
        // Vérifier si l'utilisateur a déjà fait son choix
        if (!this.hasConsented) {
            // Afficher le bandeau avec animation après un délai
            setTimeout(() => {
                cookieBanner.classList.remove('translate-y-full');
            }, 1000);
        } else {
            // S'assurer que la bannière reste cachée
            cookieBanner.classList.add('translate-y-full');
            cookieBanner.style.display = 'none';
        }
        
        // Accepter tous les cookies
        if (cookieAccept) {
            cookieAccept.addEventListener('click', () => {
                this.acceptAll();
                this.hideBanner(cookieBanner);
            });
        }
        
        // Personnaliser les préférences
        if (cookieCustomize) {
            cookieCustomize.addEventListener('click', () => {
                if (typeof window.showCookieSettings === 'function') {
                    window.showCookieSettings();
                    this.hideBanner(cookieBanner);
                } else {
                    // Rediriger vers la page de politique de cookies
                    window.location.href = cookieCustomize.dataset.cookiePage || "/politique-cookies";
                }
            });
        }
        
        // Refuser les cookies non essentiels
        if (cookieRefuse) {
            cookieRefuse.addEventListener('click', () => {
                this.savePreferences({
                    functional: false,
                    analytics: false
                });
                this.hideBanner(cookieBanner);
            });
        }
    }
    
    /**
     * Configuration de la modal de préférences de cookies
     */
    setupModal() {
        const modal = document.getElementById('cookie-preferences-modal');
        if (!modal) return;
        
        const modalContent = document.getElementById('modal-content');
        const openModalBtn = document.getElementById('open-cookie-settings');
        const closeModalBtn = document.getElementById('close-modal');
        const acceptSelectedBtn = document.getElementById('accept-selected');
        const acceptAllBtn = document.getElementById('accept-all');
        
        // Initialisation des états des checkboxes
        const functionalCheckbox = document.getElementById('functional-cookies');
        const analyticsCheckbox = document.getElementById('analytics-cookies');
        
        if (functionalCheckbox) {
            functionalCheckbox.checked = this.preferences.functional;
        }
        
        if (analyticsCheckbox) {
            analyticsCheckbox.checked = this.preferences.analytics;
        }
        
        // Ouverture de la modal
        if (openModalBtn) {
            openModalBtn.addEventListener('click', () => {
                this.openModal(modal, modalContent);
            });
            
            // Exposer la fonction globalement
            window.showCookieSettings = () => openModalBtn.click();
        }
        
        // Fermeture de la modal
        if (closeModalBtn) {
            closeModalBtn.addEventListener('click', () => {
                this.closeModal(modal, modalContent);
            });
        }
        
        // Fermeture en cliquant en dehors de la modal
        if (modal) {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal, modalContent);
                }
            });
        }
        
        // Enregistrer les préférences
        if (acceptSelectedBtn) {
            acceptSelectedBtn.addEventListener('click', () => {
                const preferences = {
                    functional: functionalCheckbox ? functionalCheckbox.checked : true,
                    analytics: analyticsCheckbox ? analyticsCheckbox.checked : true
                };
                
                this.savePreferences(preferences);
                this.closeModal(modal, modalContent);
                this.showSavedBanner();
            });
        }
        
        // Accepter tous les cookies
        if (acceptAllBtn) {
            acceptAllBtn.addEventListener('click', () => {
                this.acceptAll();
                
                // Mettre à jour les checkboxes
                if (functionalCheckbox) functionalCheckbox.checked = true;
                if (analyticsCheckbox) analyticsCheckbox.checked = true;
                
                this.closeModal(modal, modalContent);
                this.showSavedBanner();
            });
        }
    }
    
    /**
     * Ouverture de la modal avec animation
     */
    openModal(modal, modalContent) {
        if (!modal || !modalContent) return;
        
        modal.classList.remove('opacity-0', 'pointer-events-none');
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0');
            modalContent.classList.add('scale-100', 'opacity-100');
        }, 10);
    }
    
    /**
     * Fermeture de la modal avec animation
     */
    closeModal(modal, modalContent) {
        if (!modal || !modalContent) return;
        
        modalContent.classList.remove('scale-100', 'opacity-100');
        modalContent.classList.add('scale-95', 'opacity-0');
        
        setTimeout(() => {
            modal.classList.add('opacity-0', 'pointer-events-none');
        }, 300);
    }
    
    /**
     * Cacher la bannière avec animation
     */
    hideBanner(banner) {
        banner.classList.add('translate-y-full');
        setTimeout(() => {
            banner.style.display = 'none';
        }, 300);
    }
    
    /**
     * Récupère la préférence pour un type de cookie
     */
    getPreference(type, defaultValue = false) {
        const pref = localStorage.getItem(`cookiePref_${type}`);
        if (pref === null) return defaultValue;
        return pref === 'true';
    }
    
    /**
     * Enregistre les préférences utilisateur
     */
    savePreferences(preferences) {
        Object.entries(preferences).forEach(([key, value]) => {
            if (key !== 'essential') { // Les cookies essentiels sont toujours activés
                localStorage.setItem(`cookiePref_${key}`, value);
            }
        });
        
        localStorage.setItem('cookieChoice', 'custom');
        this.preferences = {...this.preferences, ...preferences};
        this.hasConsented = true;
        
        // Déclencher un événement personnalisé
        document.dispatchEvent(new CustomEvent('cookiePreferencesChanged', {
            detail: this.preferences
        }));
    }
    
    /**
     * Accepte tous les types de cookies
     */
    acceptAll() {
        localStorage.setItem('cookiePref_functional', true);
        localStorage.setItem('cookiePref_analytics', true);
        localStorage.setItem('cookieChoice', 'accepted');
        
        this.preferences.functional = true;
        this.preferences.analytics = true;
        this.hasConsented = true;
        
        document.dispatchEvent(new CustomEvent('cookiePreferencesChanged', {
            detail: this.preferences
        }));
    }
    
    /**
     * Vérifie si un type de cookie est autorisé
     */
    isAllowed(type) {
        if (type === 'essential') return true;
        if (!this.hasConsented) return false;
        return this.preferences[type] === true;
    }
    
    /**
     * Afficher une notification de confirmation
     */
    showSavedBanner() {
        const banner = document.createElement('div');
        banner.className = 'fixed bottom-4 left-1/2 transform -translate-x-1/2 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg flex items-center z-50';
        banner.innerHTML = '<i class="fas fa-check-circle mr-2"></i> Vos préférences ont été enregistrées';
        document.body.appendChild(banner);
        
        setTimeout(() => {
            banner.style.opacity = '0';
            banner.style.transition = 'opacity 0.5s ease';
            setTimeout(() => banner.remove(), 500);
        }, 3000);
    }
    
    /**
     * Réinitialise toutes les préférences
     */
    reset() {
        localStorage.removeItem('cookieChoice');
        localStorage.removeItem('cookiePref_functional');
        localStorage.removeItem('cookiePref_analytics');
        
        this.preferences = {
            essential: true,
            functional: true,
            analytics: true
        };
        this.hasConsented = false;
        
        document.dispatchEvent(new CustomEvent('cookiePreferencesReset'));
    }
}

// Initialisation du gestionnaire de cookies
window.cookieManager = new CookieManager();