/**
 * JavaScript מתקדם לווידג'ט eSIM אלמנטור פרו
 * קובץ: public/assets/js/elementor-pro-widget.js
 */

class AdProESIMProWidget {
    constructor(options = {}) {
        this.options = {
            selector: '.adpro-esim-pro-widget',
            autoInit: true,
            animateOnScroll: true,
            lazyLoad: true,
            debounceDelay: 300,
            ...options
        };
        
        this.packages = options.packages || [];
        this.filteredPackages = [...this.packages];
        this.settings = options.settings || {};
        this.favorites = this.loadFavorites();
        this.comparison = [];
        
        this.elements = {};
        this.observers = {};
        this.timers = {};
        
        if (this.options.autoInit) {
            this.init();
        }
    }
    
    /**
     * אתחול הווידג'ט
     */
    init() {
        this.cacheElements();
        this.bindEvents();
        this.initializeFeatures();
        this.setupAnimations();
        
        console.log('AdPro eSIM Pro Widget initialized', {
            packages: this.packages.length,
            settings: this.settings
        });
    }
    
    /**
     * שמירת אלמנטים בזיכרון
     */
    cacheElements() {
        this.elements = {
            widget: document.querySelector(this.options.selector),
            searchInput: document.getElementById('esim-search'),
            dataFilter: document.getElementById('data-filter'),
            durationFilter: document.getElementById('duration-filter'),
            sortFilter: document.getElementById('sort-filter'),
            resetBtn: document.getElementById('reset-filters'),
            packagesGrid: document.querySelector('.esim-packages-grid'),
            packageCards: document.querySelectorAll('.esim-package-card'),
            modal: document.getElementById('esim-package-modal'),
            modalOverlay: document.querySelector('.modal-overlay'),
            modalClose: document.querySelectorAll('.modal-close, .modal-close-btn')
        };
    }
    
    /**
     * קישור אירועים
     */
    bindEvents() {
        // פילטר חיפוש עם debounce
        if (this.elements.searchInput) {
            this.elements.searchInput.addEventListener('input', 
                this.debounce(this.handleSearch.bind(this), this.options.debounceDelay)
            );
        }
        
        // פילטרים
        [this.elements.dataFilter, this.elements.durationFilter, this.elements.sortFilter]
            .forEach(filter => {
                if (filter) {
                    filter.addEventListener('change', this.handleFilterChange.bind(this));
                }
            });
        
        // איפוס פילטרים
        if (this.elements.resetBtn) {
            this.elements.resetBtn.addEventListener('click', this.resetFilters.bind(this));
        }
        
        // כרטיסי חבילות
        this.bindPackageCardEvents();
        
        // מודאל
        this.bindModalEvents();
        
        // מועדפים והשוואה
        this.bindFavoritesEvents();
        this.bindComparisonEvents();
        
        // אירועי מקלדת
        document.addEventListener('keydown', this.handleKeyboard.bind(this));
        
        // גלילה
        window.addEventListener('scroll', 
            this.debounce(this.handleScroll.bind(this), 100)
        );
    }
    
    /**
     * קישור אירועי כרטיסי חבילות
     */
    bindPackageCardEvents() {
        // לחיצה על כרטיס - פתיחת מודאל
        this.elements.packageCards.forEach(card => {
            card.addEventListener('click', (e) => {
                if (!e.target.closest('.package-buy-button, .package-details-btn, .package-favorite, .package-compare')) {
                    this.openPackageModal(card.dataset.packageId);
                }
            });
        });
        
        // כפתור פרטים
        document.querySelectorAll('.package-details-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.openPackageModal(btn.dataset.packageId);
            });
        });
        
        // טופס רכישה
        document.querySelectorAll('.package-purchase-form').forEach(form => {
            form.addEventListener('submit', this.handlePurchase.bind(this));
        });
    }
    
    /**
     * קישור אירועי מודאל
     */
    bindModalEvents() {
        if (!this.elements.modal) return;
        
        // סגירת מודאל
        this.elements.modalClose.forEach(closeBtn => {
            closeBtn.addEventListener('click', this.closeModal.bind(this));
        });
        
        // סגירה בלחיצה על רקע
        if (this.elements.modalOverlay) {
            this.elements.modalOverlay.addEventListener('click', this.closeModal.bind(this));
        }
        
        // טופס רכישה במודאל
        const modalForm = this.elements.modal?.querySelector('.modal-purchase-form');
        if (modalForm) {
            modalForm.addEventListener('submit', this.handlePurchase.bind(this));
        }
    }
    
    /**
     * קישור אירועי מועדפים
     */
    bindFavoritesEvents() {
        document.querySelectorAll('.package-favorite').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleFavorite(btn.closest('.esim-package-card').dataset.packageId);
            });
        });
    }
    
    /**
     * קישור אירועי השוואה
     */
    bindComparisonEvents() {
        document.querySelectorAll('.package-compare').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.stopPropagation();
                this.toggleComparison(btn.closest('.esim-package-card').dataset.packageId);
            });
        });
    }
    
    /**
     * אתחול תכונות מתקדמות
     */
    initializeFeatures() {
        // טעינה עצלה של תמונות
        if (this.options.lazyLoad) {
            this.initLazyLoading();
        }
        
        // עדכון מועדפים בממשק
        this.updateFavoritesUI();
        
        // אתחול חיפוש חכם
        this.initSmartSearch();
        
        // הגדרת מתגי סטטוס
        this.setStatusBadges();
    }
    
    /**
     * הגדרת אנימציות
     */
    setupAnimations() {
        if (!this.options.animateOnScroll) return;
        
        // Intersection Observer לאנימציות בגלילה
        this.observers.scroll = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('animate-in');
                    this.observers.scroll.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.1,
            rootMargin: '0px 0px -50px 0px'
        });
        
        // צפייה בכרטיסי חבילות
        this.elements.packageCards.forEach(card => {
            this.observers.scroll.observe(card);
        });
        
        // אנימציה מדורגת
        if (this.settings.stagger_animation === 'yes') {
            this.staggerAnimation();
        }
    }
    
    /**
     * חיפוש מתקדם
     */
    handleSearch(e) {
        const query = e.target.value.toLowerCase().trim();
        
        if (!query) {
            this.filteredPackages = [...this.packages];
        } else {
            this.filteredPackages = this.packages.filter(package => {
                return this.searchInPackage(package, query);
            });
        }
        
        this.updatePackageDisplay();
        this.highlightSearchResults(query);
    }
    
    /**
     * חיפוש בתוך חבילה
     */
    searchInPackage(package, query) {
        const searchFields = [
            package.title || '',
            package.providerName || '',
            ...(package.countries || []),
            this.getPackageCountryNames(package).join(' ')
        ];
        
        return searchFields.some(field => 
            field.toLowerCase().includes(query)
        );
    }
    
    /**
     * טיפול בשינוי פילטרים
     */
    handleFilterChange() {
        const filters = {
            data: this.elements.dataFilter?.value || 'all',
            duration: this.elements.durationFilter?.value || 'all',
            sort: this.elements.sortFilter?.value || 'price-low'
        };
        
        this.applyFilters(filters);
        this.updatePackageDisplay();
        
        // שמירת מצב פילטרים
        this.saveFiltersState(filters);
    }
    
    /**
     * יישום פילטרים
     */
    applyFilters(filters) {
        let filtered = [...this.packages];
        
        // פילטר נתונים
        if (filters.data !== 'all') {
            filtered = filtered.filter(package => {
                const dataAmount = this.getPackageDataAmount(package);
                return this.matchesDataFilter(dataAmount, filters.data);
            });
        }
        
        // פילטר משך זמן
        if (filters.duration !== 'all') {
            filtered = filtered.filter(package => {
                const duration = this.getPackageDuration(package);
                return this.matchesDurationFilter(duration, filters.duration);
            });
        }
        
        // מיון
        filtered = this.sortPackages(filtered, filters.sort);
        
        this.filteredPackages = filtered;
    }
    
    /**
     * מיון חבילות
     */
    sortPackages(packages, sortType) {
        return packages.sort((a, b) => {
            switch (sortType) {
                case 'price-low':
                    return parseFloat(a.retailPrice) - parseFloat(b.retailPrice);
                case 'price-high':
                    return parseFloat(b.retailPrice) - parseFloat(a.retailPrice);
                case 'data-high':
                    return this.getPackageDataAmount(b) - this.getPackageDataAmount(a);
                case 'duration-long':
                    return this.getPackageDuration(b) - this.getPackageDuration(a);
                default:
                    return 0;
            }
        });
    }
    
    /**
     * איפוס פילטרים
     */
    resetFilters() {
        // איפוס שדות
        if (this.elements.searchInput) this.elements.searchInput.value = '';
        if (this.elements.dataFilter) this.elements.dataFilter.value = 'all';
        if (this.elements.durationFilter) this.elements.durationFilter.value = 'all';
        if (this.elements.sortFilter) this.elements.sortFilter.value = 'price-low';
        
        // איפוס מידע
        this.filteredPackages = [...this.packages];
        this.updatePackageDisplay();
        
        // אנימציה של איפוס
        this.animateReset();
    }
    
    /**
     * עדכון תצוגת חבילות
     */
    updatePackageDisplay() {
        if (!this.elements.packagesGrid) return;
        
        const cards = Array.from(this.elements.packageCards);
        
        cards.forEach(card => {
            const packageId = card.dataset.packageId;
            const shouldShow = this.filteredPackages.some(p => p.productId === packageId);
            
            if (shouldShow) {
                card.style.display = '';
                card.classList.remove('hidden');
            } else {
                card.style.display = 'none';
                card.classList.add('hidden');
            }
        });
        
        // הצגת הודעה אם אין תוצאות
        this.toggleNoResultsMessage();
        
        // עדכון מונה תוצאות
        this.updateResultsCounter();
    }
    
    /**
     * פתיחת מודאל חבילה
     */
    openPackageModal(packageId) {
        const package = this.packages.find(p => p.productId === packageId);
        if (!package || !this.elements.modal) return;
        
        // מילוי תוכן המודאל
        this.populateModal(package);
        
        // הצגת המודאל
        this.elements.modal.classList.add('active');
        document.body.style.overflow = 'hidden';
        
        // מיקוד על כפתור הסגירה
        setTimeout(() => {
            this.elements.modal.querySelector('.modal-close')?.focus();
        }, 100);
        
        // אנליטיקס
        this.trackEvent('modal_opened', { package_id: packageId });
    }
    
    /**
     * מילוי תוכן המודאל
     */
    populateModal(package) {
        const packageData = this.extractPackageData(package);
        
        // כותרת
        const titleEl = this.elements.modal.querySelector('.modal-title');
        if (titleEl) titleEl.textContent = packageData.title;
        
        // מחיר
        const priceEl = this.elements.modal.querySelector('.modal-price');
        if (priceEl) {
            priceEl.innerHTML = `${packageData.currency_symbol}${packageData.price}`;
        }
        
        // מפרטים
        this.populateModalSpecs(packageData);
        
        // רשתות
        this.populateModalNetworks(package);
        
        // מדינות
        this.populateModalCountries(package);
        
        // עדכון טופס רכישה
        this.updateModalPurchaseForm(package);
    }
    
    /**
     * מילוי מפרטים במודאל
     */
    populateModalSpecs(packageData) {
        const specsEl = this.elements.modal.querySelector('.modal-specs');
        if (!specsEl) return;
        
        specsEl.innerHTML = `
            <div class="spec-item">
                <i class="eicon-database"></i>
                <span>${packageData.data_limit} ${packageData.data_unit}</span>
            </div>
            <div class="spec-item">
                <i class="eicon-clock"></i>
                <span>${packageData.validity_days} ימים</span>
            </div>
            <div class="spec-item">
                <i class="eicon-user-circle-o"></i>
                <span>${packageData.provider_name}</span>
            </div>
        `;
    }
    
    /**
     * מילוי רשתות במודאל
     */
    populateModalNetworks(package) {
        const networksEl = this.elements.modal.querySelector('.modal-networks');
        if (!networksEl || !package.networks) return;
        
        const networksHTML = package.networks.map(network => `
            <div class="network-item">
                <span class="network-name">${network.brand}</span>
                ${network.is5G ? '<span class="tech-badge">5G</span>' : ''}
                ${network.is4G && !network.is5G ? '<span class="tech-badge">4G</span>' : ''}
            </div>
        `).join('');
        
        networksEl.innerHTML = networksHTML;
    }
    
    /**
     * מילוי מדינות במודאל
     */
    populateModalCountries(package) {
        const countriesEl = this.elements.modal.querySelector('.modal-countries');
        if (!countriesEl || !package.countries) return;
        
        const countriesHTML = package.countries.map(countryIso => {
            const countryName = this.getCountryName(countryIso);
            return `
                <div class="country-item">
                    <img src="https://flagcdn.com/24x18/${countryIso.toLowerCase()}.png" 
                         alt="${countryName}">
                    <span>${countryName}</span>
                </div>
            `;
        }).join('');
        
        countriesEl.innerHTML = countriesHTML;
    }
    
    /**
     * עדכון טופס רכישה במודאל
     */
    updateModalPurchaseForm(package) {
        const packageIdInput = this.elements.modal.querySelector('.modal-package-id');
        const countryInput = this.elements.modal.querySelector('.modal-country');
        
        if (packageIdInput) packageIdInput.value = package.productId;
        if (countryInput) countryInput.value = this.getPackageCountryNames(package)[0] || '';
    }
    
    /**
     * סגירת מודאל
     */
    closeModal() {
        if (!this.elements.modal) return;
        
        this.elements.modal.classList.remove('active');
        document.body.style.overflow = '';
        
        // מיקוד בחזרה לכרטיס שנלחץ
        const activeCard = document.querySelector('.esim-package-card:focus');
        if (activeCard) activeCard.focus();
    }
    
    /**
     * טיפול במועדפים
     */
    toggleFavorite(packageId) {
        const index = this.favorites.indexOf(packageId);
        
        if (index > -1) {
            this.favorites.splice(index, 1);
        } else {
            this.favorites.push(packageId);
        }
        
        this.saveFavorites();
        this.updateFavoritesUI();
        
        // אנימציה
        this.animateFavoriteToggle(packageId);
    }
    
    /**
     * עדכון ממשק מועדפים
     */
    updateFavoritesUI() {
        this.favorites.forEach(packageId => {
            const card = document.querySelector(`[data-package-id="${packageId}"]`);
            const favoriteBtn = card?.querySelector('.package-favorite');
            
            if (favoriteBtn) {
                favoriteBtn.classList.add('active');
                favoriteBtn.innerHTML = '<i class="eicon-heart" aria-hidden="true"></i>';
            }
        });
    }
    
    /**
     * טיפול בהשוואה
     */
    toggleComparison(packageId) {
        const index = this.comparison.indexOf(packageId);
        
        if (index > -1) {
            this.comparison.splice(index, 1);
        } else {
            if (this.comparison.length >= 3) {
                this.showMessage('ניתן להשוות עד 3 חבילות בלבד', 'warning');
                return;
            }
            this.comparison.push(packageId);
        }
        
        this.updateComparisonUI();
        this.updateComparisonFloatingButton();
    }
    
    /**
     * עדכון ממשק השוואה
     */
    updateComparisonUI() {
        this.comparison.forEach(packageId => {
            const card = document.querySelector(`[data-package-id="${packageId}"]`);
            const compareBtn = card?.querySelector('.package-compare');
            
            if (compareBtn) {
                compareBtn.classList.add('active');
            }
        });
    }
    
    /**
     * טיפול ברכישה
     */
    handlePurchase(e) {
        e.preventDefault();
        
        const form = e.target;
        const formData = new FormData(form);
        const packageId = formData.get('package_id');
        
        // הצגת אנימציית טעינה
        this.showLoadingState(form);
        
        // אנליטיקס
        this.trackEvent('purchase_initiated', { package_id: packageId });
        
        // שליחת הטופס
        form.submit();
    }
    
    /**
     * הצגת מצב טעינה
     */
    showLoadingState(form) {
        const button = form.querySelector('button[type="submit"]');
        if (!button) return;
        
        button.classList.add('loading');
        button.disabled = true;
        
        const originalText = button.innerHTML;
        button.innerHTML = '<i class="eicon-loading eicon-animation-spin"></i> מעבד...';
        
        // החזרה למצב רגיל אחרי 3 שניות (fallback)
        setTimeout(() => {
            button.classList.remove('loading');
            button.disabled = false;
            button.innerHTML = originalText;
        }, 3000);
    }
    
    /**
     * טיפול באירועי מקלדת
     */
    handleKeyboard(e) {
        // ESC - סגירת מודאל
        if (e.key === 'Escape' && this.elements.modal?.classList.contains('active')) {
            this.closeModal();
        }
        
        // Ctrl+F - מיקוד על חיפוש
        if (e.ctrlKey && e.key === 'f' && this.elements.searchInput) {
            e.preventDefault();
            this.elements.searchInput.focus();
        }
    }
    
    /**
     * טיפול בגלילה
     */
    handleScroll() {
        // Sticky filters
        this.updateStickyFilters();
        
        // Lazy loading
        this.checkLazyLoading();
        
        // Progress indicator
        this.updateScrollProgress();
    }
    
    /**
     * עדכון פילטרים דביקים
     */
    updateStickyFilters() {
        const filtersSection = document.querySelector('.esim-filters-section');
        if (!filtersSection) return;
        
        const rect = filtersSection.getBoundingClientRect();
        const isSticky = rect.top <= 20;
        
        filtersSection.classList.toggle('is-sticky', isSticky);
    }
    
    /**
     * אתחול טעינה עצלה
     */
    initLazyLoading() {
        this.observers.lazyLoad = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy');
                    this.observers.lazyLoad.unobserve(img);
                }
            });
        });
        
        document.querySelectorAll('img[data-src]').forEach(img => {
            this.observers.lazyLoad.observe(img);
        });
    }
    
    /**
     * פונקציות עזר
     */
    
    debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }
    
    extractPackageData(package) {
        // חילוץ נתוני חבילה (כמו בקובץ PHP)
        const data = {
            title: this.getPackageDetail(package, 'PLAN_TITLE') || 'חבילת eSIM',
            price: package.retailPrice || 0,
            currency_symbol: this.getCurrencySymbol(package.currencyCode),
            data_limit: this.getPackageDetail(package, 'PLAN_DATA_LIMIT') || '',
            data_unit: this.getPackageDetail(package, 'PLAN_DATA_UNIT') || '',
            validity_days: Math.round(parseInt(this.getPackageDetail(package, 'PLAN_VALIDITY') || 0) / 24),
            provider_name: package.providerName || '',
            country_name: this.getPackageCountryNames(package)[0] || '',
            country_iso: package.countries?.[0] || ''
        };
        
        return data;
    }
    
    getPackageDetail(package, name) {
        if (!package.productDetails) return '';
        const detail = package.productDetails.find(d => d.name === name);
        return detail ? detail.value : '';
    }
    
    getCurrencySymbol(currency) {
        const symbols = {
            'USD': '$',
            'EUR': '€',
            'GBP': '£',
            'ILS': '₪'
        };
        return symbols[currency] || currency;
    }
    
    getPackageCountryNames(package) {
        // TODO: מיפוי ISO לשמות בעברית
        return package.countries || [];
    }
    
    getCountryName(iso) {
        // TODO: מיפוי ISO לשם בעברית
        return iso;
    }
    
    getPackageDataAmount(package) {
        const limit = this.getPackageDetail(package, 'PLAN_DATA_LIMIT');
        return parseFloat(limit) || 0;
    }
    
    getPackageDuration(package) {
        const validity = this.getPackageDetail(package, 'PLAN_VALIDITY');
        return Math.round(parseInt(validity) / 24) || 0;
    }
    
    matchesDataFilter(amount, filter) {
        switch (filter) {
            case '1-5': return amount >= 1 && amount <= 5;
            case '5-10': return amount > 5 && amount <= 10;
            case '10-20': return amount > 10 && amount <= 20;
            case '20+': return amount > 20;
            default: return true;
        }
    }
    
    matchesDurationFilter(duration, filter) {
        switch (filter) {
            case '1-7': return duration >= 1 && duration <= 7;
            case '7-30': return duration > 7 && duration <= 30;
            case '30+': return duration > 30;
            default: return true;
        }
    }
    
    highlightSearchResults(query) {
        if (!query) return;
        
        document.querySelectorAll('.esim-package-card:not(.hidden)').forEach(card => {
            const textNodes = this.getTextNodes(card);
            textNodes.forEach(node => {
                if (node.textContent.toLowerCase().includes(query)) {
                    const highlightedText = node.textContent.replace(
                        new RegExp(`(${query})`, 'gi'),
                        '<mark>$1</mark>'
                    );
                    node.parentNode.innerHTML = highlightedText;
                }
            });
        });
    }
    
    getTextNodes(element) {
        const textNodes = [];
        const walker = document.createTreeWalker(
            element,
            NodeFilter.SHOW_TEXT,
            null,
            false
        );
        
        let node;
        while (node = walker.nextNode()) {
            textNodes.push(node);
        }
        
        return textNodes;
    }
    
    toggleNoResultsMessage() {
        const visibleCards = document.querySelectorAll('.esim-package-card:not(.hidden)');
        let noResultsEl = document.querySelector('.esim-no-results');
        
        if (visibleCards.length === 0) {
            if (!noResultsEl) {
                noResultsEl = document.createElement('div');
                noResultsEl.className = 'esim-no-results';
                noResultsEl.innerHTML = `
                    <div class="no-results-content">
                        <i class="eicon-search"></i>
                        <h3>לא נמצאו תוצאות</h3>
                        <p>נסה לשנות את הפילטרים או את מילות החיפוש</p>
                    </div>
                `;
                this.elements.packagesGrid.appendChild(noResultsEl);
            }
            noResultsEl.style.display = 'block';
        } else {
            if (noResultsEl) {
                noResultsEl.style.display = 'none';
            }
        }
    }
    
    updateResultsCounter() {
        const counter = document.querySelector('.results-counter');
        if (!counter) return;
        
        const visibleCount = document.querySelectorAll('.esim-package-card:not(.hidden)').length;
        const totalCount = this.packages.length;
        
        counter.textContent = `מציג ${visibleCount} מתוך ${totalCount} חבילות`;
    }
    
    animateReset() {
        this.elements.packagesGrid?.classList.add('resetting');
        setTimeout(() => {
            this.elements.packagesGrid?.classList.remove('resetting');
        }, 300);
    }
    
    animateFavoriteToggle(packageId) {
        const card = document.querySelector(`[data-package-id="${packageId}"]`);
        const btn = card?.querySelector('.package-favorite');
        
        if (btn) {
            btn.classList.add('animate-heart');
            setTimeout(() => {
                btn.classList.remove('animate-heart');
            }, 600);
        }
    }
    
    staggerAnimation() {
        this.elements.packageCards.forEach((card, index) => {
            card.style.animationDelay = `${index * 100}ms`;
        });
    }
    
    updateComparisonFloatingButton() {
        let floatingBtn = document.querySelector('.comparison-floating-btn');
        
        if (this.comparison.length > 0) {
            if (!floatingBtn) {
                floatingBtn = document.createElement('div');
                floatingBtn.className = 'comparison-floating-btn';
                floatingBtn.innerHTML = `
                    <button type="button" class="compare-btn">
                        <i class="eicon-select"></i>
                        <span>השווה ${this.comparison.length} חבילות</span>
                    </button>
                `;
                document.body.appendChild(floatingBtn);
                
                floatingBtn.addEventListener('click', () => {
                    this.showComparisonModal();
                });
            } else {
                const span = floatingBtn.querySelector('span');
                if (span) span.textContent = `השווה ${this.comparison.length} חבילות`;
            }
            
            floatingBtn.style.display = 'block';
        } else {
            if (floatingBtn) {
                floatingBtn.style.display = 'none';
            }
        }
    }
    
    showComparisonModal() {
        // TODO: הצגת מודאל השוואה
        console.log('Comparison packages:', this.comparison);
    }
    
    showMessage(message, type = 'info') {
        const messageEl = document.createElement('div');
        messageEl.className = `esim-message esim-message-${type}`;
        messageEl.innerHTML = `
            <div class="message-content">
                <i class="eicon-info-circle"></i>
                <span>${message}</span>
                <button type="button" class="message-close">
                    <i class="eicon-close"></i>
                </button>
            </div>
        `;
        
        document.body.appendChild(messageEl);
        
        // אנימציה
        setTimeout(() => messageEl.classList.add('show'), 100);
        
        // סגירה אוטומטית
        setTimeout(() => {
            messageEl.classList.remove('show');
            setTimeout(() => messageEl.remove(), 300);
        }, 4000);
        
        // סגירה ידנית
        messageEl.querySelector('.message-close').addEventListener('click', () => {
            messageEl.classList.remove('show');
            setTimeout(() => messageEl.remove(), 300);
        });
    }
    
    checkLazyLoading() {
        // בדיקת טעינה עצלה נוספת במידת הצורך
        if (!this.observers.lazyLoad) return;
        
        document.querySelectorAll('img[data-src]:not(.lazy-loading)').forEach(img => {
            this.observers.lazyLoad.observe(img);
        });
    }
    
    updateScrollProgress() {
        const progressBar = document.querySelector('.scroll-progress');
        if (!progressBar) return;
        
        const scrolled = window.pageYOffset;
        const maxScroll = document.body.scrollHeight - window.innerHeight;
        const progress = (scrolled / maxScroll) * 100;
        
        progressBar.style.width = `${Math.min(progress, 100)}%`;
    }
    
    initSmartSearch() {
        // אתחול חיפוש חכם עם הצעות
        if (!this.elements.searchInput) return;
        
        const suggestionsContainer = document.createElement('div');
        suggestionsContainer.className = 'search-suggestions';
        this.elements.searchInput.parentNode.appendChild(suggestionsContainer);
        
        this.elements.searchInput.addEventListener('input', (e) => {
            const query = e.target.value.toLowerCase().trim();
            
            if (query.length >= 2) {
                this.showSearchSuggestions(query, suggestionsContainer);
            } else {
                suggestionsContainer.style.display = 'none';
            }
        });
        
        // סגירת הצעות בלחיצה מחוץ לתיבה
        document.addEventListener('click', (e) => {
            if (!this.elements.searchInput.contains(e.target)) {
                suggestionsContainer.style.display = 'none';
            }
        });
    }
    
    showSearchSuggestions(query, container) {
        const suggestions = this.getSearchSuggestions(query);
        
        if (suggestions.length === 0) {
            container.style.display = 'none';
            return;
        }
        
        container.innerHTML = suggestions.map(suggestion => `
            <div class="suggestion-item" data-value="${suggestion}">
                <i class="eicon-search"></i>
                <span>${suggestion}</span>
            </div>
        `).join('');
        
        container.style.display = 'block';
        
        // קישור לחיצות על הצעות
        container.querySelectorAll('.suggestion-item').forEach(item => {
            item.addEventListener('click', () => {
                this.elements.searchInput.value = item.dataset.value;
                this.elements.searchInput.dispatchEvent(new Event('input'));
                container.style.display = 'none';
            });
        });
    }
    
    getSearchSuggestions(query) {
        const suggestions = new Set();
        
        this.packages.forEach(package => {
            // מדינות
            if (package.countries) {
                package.countries.forEach(country => {
                    const countryName = this.getCountryName(country);
                    if (countryName.toLowerCase().includes(query)) {
                        suggestions.add(countryName);
                    }
                });
            }
            
            // ספקים
            if (package.providerName && package.providerName.toLowerCase().includes(query)) {
                suggestions.add(package.providerName);
            }
            
            // כמות נתונים
            const dataAmount = this.getPackageDataAmount(package);
            if (dataAmount && dataAmount.toString().includes(query)) {
                const unit = this.getPackageDetail(package, 'PLAN_DATA_UNIT') || 'GB';
                suggestions.add(`${dataAmount}${unit}`);
            }
        });
        
        return Array.from(suggestions).slice(0, 5);
    }
    
    setStatusBadges() {
        this.elements.packageCards.forEach(card => {
            const packageId = card.dataset.packageId;
            const package = this.packages.find(p => p.productId === packageId);
            
            if (!package) return;
            
            // בדיקת מחיר מוזל
            const originalPrice = parseFloat(package.originalPrice || package.retailPrice);
            const currentPrice = parseFloat(package.retailPrice);
            
            if (originalPrice > currentPrice) {
                const badge = document.createElement('div');
                badge.className = 'status-badge featured';
                badge.innerHTML = '<i class="eicon-tags"></i> מבצע';
                card.appendChild(badge);
            }
            
            // בדיקת פופולריות (לדוגמה)
            if (this.isPopularPackage(package)) {
                const badge = document.createElement('div');
                badge.className = 'status-badge popular';
                badge.innerHTML = '<i class="eicon-star"></i> פופולרי';
                card.appendChild(badge);
            }
        });
    }
    
    isPopularPackage(package) {
        // לוגיקה לקביעת פופולריות - לדוגמה
        const dataAmount = this.getPackageDataAmount(package);
        const duration = this.getPackageDuration(package);
        const price = parseFloat(package.retailPrice);
        
        // פופולרי אם יש יחס טוב של נתונים למחיר
        const dataPerDollar = dataAmount / price;
        return dataPerDollar > 0.5 && duration >= 7;
    }
    
    loadFavorites() {
        try {
            const saved = localStorage.getItem('esim_favorites');
            return saved ? JSON.parse(saved) : [];
        } catch (e) {
            console.warn('Failed to load favorites from localStorage:', e);
            return [];
        }
    }
    
    saveFavorites() {
        try {
            localStorage.setItem('esim_favorites', JSON.stringify(this.favorites));
        } catch (e) {
            console.warn('Failed to save favorites to localStorage:', e);
        }
    }
    
    saveFiltersState(filters) {
        try {
            sessionStorage.setItem('esim_filters', JSON.stringify(filters));
        } catch (e) {
            console.warn('Failed to save filters state:', e);
        }
    }
    
    loadFiltersState() {
        try {
            const saved = sessionStorage.getItem('esim_filters');
            return saved ? JSON.parse(saved) : null;
        } catch (e) {
            console.warn('Failed to load filters state:', e);
            return null;
        }
    }
    
    trackEvent(eventName, data = {}) {
        // Google Analytics / GTM tracking
        if (typeof gtag !== 'undefined') {
            gtag('event', eventName, {
                event_category: 'eSIM_Widget',
                ...data
            });
        }
        
        // Facebook Pixel
        if (typeof fbq !== 'undefined') {
            fbq('track', 'CustomEvent', {
                event_name: eventName,
                ...data
            });
        }
        
        console.log('Event tracked:', eventName, data);
    }
    
    destroy() {
        // ניקוי observers
        Object.values(this.observers).forEach(observer => {
            if (observer && observer.disconnect) {
                observer.disconnect();
            }
        });
        
        // ניקוי timers
        Object.values(this.timers).forEach(timer => {
            clearTimeout(timer);
        });
        
        // הסרת event listeners
        document.removeEventListener('keydown', this.handleKeyboard.bind(this));
        window.removeEventListener('scroll', this.handleScroll.bind(this));
        
        console.log('AdPro eSIM Pro Widget destroyed');
    }
}

// אתחול אוטומטי כאשר הדף נטען
document.addEventListener('DOMContentLoaded', function() {
    // בדיקה אם יש נתוני חבילות זמינים
    if (typeof window.esimPackagesData !== 'undefined' && 
        typeof window.esimWidgetSettings !== 'undefined') {
        
        new AdProESIMProWidget({
            packages: window.esimPackagesData,
            settings: window.esimWidgetSettings
        });
    }
});

// אתחול עבור Elementor Editor
jQuery(window).on('elementor/frontend/init', function() {
    elementorFrontend.hooks.addAction('frontend/element_ready/adpro_esim_pro_packages.default', function($scope) {
        // אתחול הווידג'ט בעורך אלמנטור
        const widget = $scope.find('.adpro-esim-pro-widget')[0];
        if (widget && typeof window.esimPackagesData !== 'undefined') {
            new AdProESIMProWidget({
                selector: '.adpro-esim-pro-widget',
                packages: window.esimPackagesData,
                settings: window.esimWidgetSettings || {}
            });
        }
    });
});

// Export לשימוש במודולים
if (typeof module !== 'undefined' && module.exports) {
    module.exports = AdProESIMProWidget;
}