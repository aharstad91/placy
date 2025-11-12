/**
 * Component factory for generating reusable UI components
 * Reduces code duplication and improves maintainability
 */

class ComponentFactory {
    constructor() {
        this.cache = new Map();
    }
    
    /**
     * Generate navigation component
     */
    createNavigation(config) {
        const nav = document.createElement('header');
        nav.className = 'bg-[#76908D] fixed top-0 left-0 right-0 z-50';
        
        nav.innerHTML = `
            <div class="max-w-7xl mx-auto px-6 py-4">
                <div class="flex items-center justify-between">
                    <!-- Logo -->
                    <div class="flex-shrink-0">
                        <img src="${config.logo}" alt="Overvik" class="h-8 w-auto">
                    </div>
                    
                    <!-- Navigation Menu (Desktop) -->
                    <nav class="hidden md:flex space-x-8 nav-font">
                        ${config.menuItems.map(item => 
                            `<a href="${item.href}" class="text-gray-700 hover:text-overvik-green transition-colors font-medium">${item.text}</a>`
                        ).join('')}
                    </nav>
                    
                    <!-- CTA Button -->
                    <div>
                        <a href="${config.cta.href}" class="bg-overvik-green text-white py-2 px-4 rounded-full nav-font font-medium hover:bg-opacity-90 transition-all">
                            ${config.cta.text}
                        </a>
                    </div>
                </div>
            </div>
        `;
        
        return nav;
    }
    
    /**
     * Generate hero section
     */
    createHero(config) {
        const hero = document.createElement('section');
        hero.className = 'relative w-full bg-cover bg-center bg-no-repeat flex items-end';
        hero.style.cssText = `
            background-image: url('${config.backgroundImage}');
            height: calc(var(--viewport-height, 100vh) * 0.9);
        `;
        
        hero.innerHTML = `
            <!-- Overlay for better text readability -->
            <div class="absolute inset-0 bg-black bg-opacity-30"></div>
            
            <!-- Hero content positioned at bottom -->
            <div class="relative z-10 w-full px-6 pb-16">
                <div class="max-w-4xl">
                    <h1 class="text-4xl md:text-5xl font-bold text-white mb-6 drop-shadow-lg font-campaign-serif">
                        ${config.title}
                    </h1>
                    <p class="text-white text-lg md:text-xl leading-relaxed drop-shadow-md font-campaign">
                        ${config.subtitle}
                    </p>
                </div>
            </div>
        `;
        
        return hero;
    }
    
    /**
     * Generate table of contents
     */
    createTableOfContents(config) {
        const toc = document.createElement('section');
        toc.className = 'table-of-contents bg-gray-50 py-16';
        
        toc.innerHTML = `
            <div class="max-w-4xl mx-auto px-6">
                <div class="text-center mb-12">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4 font-campaign-serif">${config.title}</h2>
                    <p class="text-gray-600 text-lg">${config.subtitle}</p>
                </div>
                
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    ${config.items.map(item => `
                        <a href="#${item.id}" class="toc-item" onclick="event.preventDefault(); navigateToSection('${item.id}');">
                            <div class="toc-icon">
                                <span>${item.icon}</span>
                            </div>
                            <div class="toc-content">
                                <h3>${item.title}</h3>
                                <p>${item.description}</p>
                            </div>
                        </a>
                    `).join('')}
                </div>
            </div>
        `;
        
        return toc;
    }
    
    /**
     * Generate section component
     */
    createSection(id, config) {
        const section = document.createElement('section');
        section.id = id;
        section.className = 'section px-6 py-6 mb-16';
        
        const icon = ICON_MAP[config.icon] || '';
        
        section.innerHTML = `
            <!-- Section Image -->
            <div class="relative bg-gray-200 rounded-lg mb-6" style="height: calc(var(--viewport-height, 100vh) * 0.33);">
                <div class="absolute top-4 left-4 w-8 h-8 text-overvik-green bg-white rounded-full p-2 shadow-md">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        ${icon}
                    </svg>
                </div>
            </div>
            
            <header class="section-header">
                <div class="mb-4">
                    <h1 class="text-2xl font-bold text-gray-900 w-full text-left">${config.title}</h1>
                </div>
                <p class="text-gray-700 text-base leading-relaxed mb-6">
                    ${config.description}
                </p>
            </header>
            
            <div class="section-content">
                <div>
                    <!-- Map Placeholder -->
                    <div class="bg-gray-200 rounded-lg h-48 flex items-center justify-center mb-6 clickable" onclick="openMapModal('${id}')">
                        <div class="text-center">
                            <div class="text-gray-600 text-lg font-medium">${config.title} Kart</div>
                            <div class="text-gray-500 text-sm mt-1">Klikk for fullskjerm</div>
                        </div>
                    </div>
                    
                    <!-- Cards Section -->
                    <div class="overflow-x-auto horizontal-scroll clickable mb-16" onclick="openMapModal('${id}')">
                        ${this.createCardsContainer(config.cards)}
                    </div>
                </div>
            </div>
        `;
        
        return section;
    }
    
    /**
     * Generate cards container
     */
    createCardsContainer(cards) {
        if (!cards || cards.length === 0) return '';
        
        return `
            <div class="flex gap-4 pb-6" style="width: max-content;">
                ${cards.map(card => this.createCard(card)).join('')}
            </div>
        `;
    }
    
    /**
     * Generate individual card
     */
    createCard(cardData) {
        if (cardData.type === 'link') {
            return `
                <div class="bg-overvik-light rounded-lg shadow-md overflow-hidden cursor-pointer hover:bg-opacity-90 transition-all" style="width: 33vw; min-width: 200px;">
                    <div class="p-6 h-full flex flex-col justify-center items-center text-center">
                        <div class="w-8 h-8 mb-3 text-overvik-green">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M9 18l6-6-6-6"/>
                            </svg>
                        </div>
                        <h3 class="font-semibold text-overvik-green mb-2">${cardData.title}</h3>
                        <p class="text-gray-700 text-sm">${cardData.description}</p>
                    </div>
                </div>
            `;
        }
        
        return `
            <div class="bg-white rounded-lg shadow-md overflow-hidden" style="width: 33vw; min-width: 200px;">
                <div class="h-24 bg-gray-200"></div>
                <div class="p-4">
                    <h3 class="font-semibold text-gray-900 mb-2">${cardData.title}</h3>
                    <p class="text-gray-600 text-sm">${cardData.description}</p>
                </div>
            </div>
        `;
    }
    
    /**
     * Generate sticky navigation
     */
    createStickyNavigation(tocConfig) {
        const container = document.createElement('div');
        container.className = 'sticky-nav-container';
        container.id = 'stickyNavContainer';
        
        container.innerHTML = `
            <div class="sticky-nav-button sticky-button-base" onclick="toggleStickyNav()">
                <div class="sticky-nav-hamburger">
                    <div class="hamburger-icon">
                        <span></span>
                        <span></span>
                    </div>
                </div>
                <span class="sticky-nav-label">Finn ditt tema</span>
                <span class="sticky-nav-counter">${tocConfig.items.length}</span>
            </div>
            
            <div class="sticky-contact sticky-button-base" onclick="openContact()">
                <span class="contact-label">Kontakt</span>
            </div>
            
            <div class="sticky-scroll-top sticky-button-base" onclick="scrollToTop()">
                <div class="scroll-top-icon">↑</div>
            </div>
        `;
        
        return container;
    }
    
    /**
     * Generate sticky navigation popup
     */
    createStickyNavPopup(tocConfig) {
        const popup = document.createElement('div');
        popup.className = 'nav-popup';
        popup.id = 'stickyNavPopup';
        
        popup.innerHTML = `
            <div class="nav-popup-header">
                <h3>${tocConfig.title}</h3>
            </div>
            <div class="nav-popup-content">
                ${tocConfig.items.map(item => `
                    <a href="#${item.id}" class="nav-popup-item" onclick="navigateToSection('${item.id}')">
                        <div class="nav-popup-icon">${item.icon}</div>
                        <div class="nav-popup-text">
                            <h4>${item.title}</h4>
                            <p>${item.description}</p>
                        </div>
                    </a>
                `).join('')}
            </div>
        `;
        
        return popup;
    }
    
    /**
     * Cache and retrieve components
     */
    getCachedComponent(key, generator) {
        if (this.cache.has(key)) {
            return this.cache.get(key).cloneNode(true);
        }
        
        const component = generator();
        this.cache.set(key, component.cloneNode(true));
        return component;
    }
    
    /**
     * Clear component cache
     */
    clearCache() {
        this.cache.clear();
    }
}

// Create global instance
const componentFactory = new ComponentFactory();

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { ComponentFactory, componentFactory };
}
