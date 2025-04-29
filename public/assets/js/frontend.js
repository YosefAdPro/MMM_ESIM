jQuery(document).ready(function ($) {
    console.log('Script loaded successfully');
	
	


    // תמיכה בסינון צף
    if ($('.package-filters').length) {
        // מוסיף את ה-sticky class מיד לאלמנט אם המשתמש במובייל
        if (window.innerWidth <= 768) {
            $('.package-filters').addClass('sticky');
        } else {
            // במסך רגיל, מחשב את המיקום וצף בהתאם לגלילה
            const filterOffset = $('.package-filters').offset().top;
            
            $(window).on('scroll', function() {
                const scrollDistance = $(window).scrollTop();
                
                if (scrollDistance > filterOffset) {
                    $('.package-filters').addClass('sticky');
                } else {
                    $('.package-filters').removeClass('sticky');
                }
            });
        }
        
        // עדכון בעת שינוי גודל חלון
        $(window).on('resize', function() {
            if (window.innerWidth <= 768) {
                $('.package-filters').addClass('sticky');
            } else {
                // בדוק שוב את מיקום הגלילה
                const filterOffset = $('.package-filters').offset().top;
                const scrollDistance = $(window).scrollTop();
                
                if (scrollDistance > filterOffset) {
                    $('.package-filters').addClass('sticky');
                } else {
                    $('.package-filters').removeClass('sticky');
                }
            }
        });
    }



    // ניהול סינון במובייל
    function toggleMobileFilters() {
        $('.package-filters').toggleClass('filters-open');
        const buttonText = $('.package-filters').hasClass('filters-open') ? 'סגור סינון' : 'פתח סינון מתקדם';
        $('.filter-mobile-toggle').text(buttonText);
        $('.sticky-filter-button').text(buttonText);
    }

    function handleOutsideClick(e) {
        if (
            !$(e.target).closest('.package-filters').length &&
            !$(e.target).is('.filter-mobile-toggle') &&
            !$(e.target).is('.sticky-filter-button') &&
            $('.package-filters').hasClass('filters-open') &&
            window.innerWidth <= 768
        ) {
            $('.package-filters').removeClass('filters-open');
            $('.filter-mobile-toggle').text('פתח סינון מתקדם');
            $('.sticky-filter-button').text('פתח סינון מתקדם');
        }
    }

    function adjustFilterVisibility() {
        if (window.innerWidth > 768) {
            $('.filter-content').show();
        } else {
            $('.filter-content').toggle($('.package-filters').hasClass('filters-open'));
        }
    }

    // רישום אירועים לסינון במובייל
    $(document).on('click', '.filter-mobile-toggle', toggleMobileFilters);
    $(document).on('click', handleOutsideClick);
    $(window).on('resize', adjustFilterVisibility);
    adjustFilterVisibility();

    // סינון ומיון חבילות
    if ($('.packages-list').length) {
function filterPackages() {
    const dataFilter = $('#data-filter').val();
    const durationFilter = $('#duration-filter').val();

    $('.package').each(function () {
        const $package = $(this);
        
        // חיפוש נתונים במבנה החדש - מחפש את הטקסט בתוך info-value
        let dataAmount = 0;
        let durationDays = 0;
        
        // חיפוש נתוני GB ותוקף
        $package.find('.info-item').each(function() {
            const label = $(this).find('.info-label').text().trim();
            const value = $(this).find('.info-value').text().trim();
            
            if (label.includes('נתונים')) {
                dataAmount = parseFloat(value.match(/(\d+(\.\d+)?)/i) || 0);
            }
            
            if (label.includes('תוקף')) {
                durationDays = parseInt(value.match(/(\d+)/i) || 0);
            }
        });

        const showByData =
            dataFilter === 'all' ||
            (dataFilter === '1-5' && dataAmount >= 1 && dataAmount <= 5) ||
            (dataFilter === '5-10' && dataAmount > 5 && dataAmount <= 10) ||
            (dataFilter === '10-20' && dataAmount > 10 && dataAmount <= 20) ||
            (dataFilter === '20-50' && dataAmount > 20 && dataAmount <= 50) ||
            (dataFilter === '50+' && dataAmount > 50);

        const showByDuration =
            durationFilter === 'all' ||
            (durationFilter === '1-7' && durationDays >= 1 && durationDays <= 7) ||
            (durationFilter === '7-14' && durationDays > 7 && durationDays <= 14) ||
            (durationFilter === '14-30' && durationDays > 14 && durationDays <= 30) ||
            (durationFilter === '30-90' && durationDays > 30 && durationDays <= 90) ||
            (durationFilter === '90+' && durationDays > 90);

        $package.toggleClass('hidden-package', !(showByData && showByDuration));
    });

    $('.no-filtered-packages').remove();
    if ($('.package:not(.hidden-package)').length === 0) {
        $('.packages-list').append(
            '<div class="no-filtered-packages"><p>לא נמצאו חבילות העונות לקריטריוני הסינון.</p></div>'
        );
    }
}

        function sortPackagesByPrice() {
            const sortDirection = $('#price-filter').val();
            if (sortDirection === 'all') return;

            const $packagesContainer = $('.packages-list');
            const $packages = $packagesContainer.children('.package').get();

            $packages.sort(function (a, b) {
                const priceA = parseFloat($(a).find('.price').text().replace(/[^\d.]/g, '')) || 0;
                const priceB = parseFloat($(b).find('.price').text().replace(/[^\d.]/g, '')) || 0;
                return sortDirection === 'low-to-high' ? priceA - priceB : priceB - priceA;
            });

            $packagesContainer.empty().append($packages);
        }

        $('#data-filter, #duration-filter').on('change', filterPackages);
        $('#price-filter').on('change', sortPackagesByPrice);
        $('#reset-filters').on('click', function () {
            $('#data-filter, #duration-filter, #price-filter').val('all');
            $('.package').removeClass('hidden-package');
            $('.no-filtered-packages').remove();
        });
    }

    // חיפוש מדינות
    const countries = AdPro_esim_ajax?.countries || {};
    const $searchInput = $('#country-search');
    const $suggestionsContainer = $('#country-suggestions');

    $searchInput.on('input', function () {
        const input = $(this).val().trim().toLowerCase();
        if (!input) {
            $suggestionsContainer.empty().hide();
            return;
        }

        const matches = Object.keys(countries)
            .filter((hebrew) => hebrew.toLowerCase().includes(input))
            .map((hebrew) => ({
                hebrew,
                iso: countries[hebrew].iso,
                slug: countries[hebrew].slug,
            }))
            .sort((a, b) => a.hebrew.localeCompare(b.hebrew));

        $suggestionsContainer.empty();
        if (matches.length) {
            matches.forEach((match) => {
                $suggestionsContainer.append(
                    `<div class="suggestion" data-country="${match.slug}">
                        <img src="https://flagcdn.com/16x12/${match.iso.toLowerCase()}.png" alt="${match.hebrew}">
                        <span>${match.hebrew}</span>
                    </div>`
                );
            });
        } else {
            $suggestionsContainer.append('<div class="no-results">לא נמצאו תוצאות</div>');
        }
        $suggestionsContainer.show();
    });

    $(document).on('click', '.suggestion', function () {
        window.location.href = `${AdPro_esim_ajax.site_url}/esim/${$(this).data('country')}`;
    });

    $(document).on('click', (e) => {
        if (!$(e.target).closest('.search-box').length) $suggestionsContainer.hide();
    });

    $searchInput.on('keydown', function (e) {
        const $suggestions = $('.suggestion');
        const $selected = $('.suggestion.selected');
        let $current;

        if (e.keyCode === 40) { // חץ למטה
            $current = $selected.length ? $selected.next() : $suggestions.first();
        } else if (e.keyCode === 38) { // חץ למעלה
            $current = $selected.length ? $selected.prev() : $suggestions.last();
        } else if (e.keyCode === 13 && $selected.length) { // Enter
            e.preventDefault();
            $selected.click();
            return;
        } else {
            return;
        }

        $suggestions.removeClass('selected');
        $current.addClass('selected').get(0)?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // ניהול מודאל חבילות
// עדכון פונקציית יצירת המודאל
$(document).on('click', '.package-clickable', function (e) {
    if ($(e.target).closest('.buy-now').length) return;

    const packageId = $(this).data('package-id');
    const packageDetails = packageData.find((p) => p.productId === packageId);
    if (!packageDetails) return;

    const { productDetails, retailPrice, currencyCode, providerName, countries } = packageDetails;
    const dataLimit = productDetails?.find((d) => d.name === 'PLAN_DATA_LIMIT')?.value || '';
    const dataUnit = productDetails?.find((d) => d.name === 'PLAN_DATA_UNIT')?.value || '';
    const validityDays = productDetails?.find((d) => d.name === 'PLAN_VALIDITY')?.value
        ? Math.round(parseInt(productDetails.find((d) => d.name === 'PLAN_VALIDITY').value) / 24)
        : '';
    const planTitle = productDetails?.find((d) => d.name === 'PLAN_TITLE')?.value || packageId;

    let customTitle = hebrewCountry ? `חבילת גלישה ל${hebrewCountry}` : '';
    if (countries?.length > 1) {
        customTitle += ` ועוד ${countries.length - 1} מדינות`;
    } else if (!hebrewCountry && countries?.length) {
        customTitle = `חבילת גלישה ל${countries.length} מדינות`;
    }
    if (dataLimit && dataUnit) customTitle += ` עם ${dataLimit} ${dataUnit}`;
    if (validityDays) customTitle += ` למשך ${validityDays} ימים`;

    let modalContent = `<h2>${customTitle}</h2>`;
    modalContent += '<div class="modal-details">';
    modalContent += '<div class="details-section">';
    modalContent += '<h4>פרטי חבילה</h4>';

    // עדכון הצגת המחיר - במקום להציג currencyCode נציג אייקון
    let currencySymbol = '';
    if (currencyCode === 'USD') {
        currencySymbol = '$';
    } else if (currencyCode === 'EUR') {
        currencySymbol = '€';
    } else if (currencyCode === 'GBP') {
        currencySymbol = '£';
    } else {
        currencySymbol = currencyCode;
    }
    
    // שינוי סדר ההצגה - קודם המחיר ואז סימן המטבע
    modalContent += `<p class="modal-price">${retailPrice} <span class="currency-symbol">${currencySymbol}</span></p>`;
    
    if (dataLimit && dataUnit) modalContent += `<p><strong>נתונים:</strong> ${dataLimit} ${dataUnit}</p>`;
    if (validityDays) modalContent += `<p><strong>תוקף:</strong> ${validityDays} ימים</p>`;
    if (providerName) modalContent += `<p><strong>ספק:</strong> ${providerName}</p>`;
    modalContent += '<div class="package-description">';
    modalContent += `<p>חבילת גלישה ל${hebrewCountry || countries?.length + ' מדינות'}</p>`;
    modalContent += '</div></div>';


if (countries?.length) {
    modalContent += '<div class="details-section">';
    modalContent += `<h4>כל המדינות הנתמכות (${countries.length})</h4>`;
    
    // מתחיל טבלה דו-טורית
    modalContent += '<div class="countries-supported-grid">';
    
    // מסדר את המדינות בזוגות (שמאל-ימין)
    for (let i = 0; i < countries.length; i += 2) {
        modalContent += '<div class="country-row">';
        
        // הצד הימני של השורה (אם קיים)
        if (i < countries.length) {
            const rightCountryCode = countries[i];
            const displayCodeRight = rightCountryCode.split('-')[0];
            const countryNameRight = Object.keys(countriesMapping || {}).find(
                (key) => countriesMapping[key].iso === displayCodeRight
            ) || rightCountryCode;
            
            modalContent += `
                <div class="country-cell">
                    <div class="country-flag-name">
                        <span class="country-name">${countryNameRight}</span>
                        <img src="https://flagcdn.com/24x18/${displayCodeRight.toLowerCase()}.png" alt="${countryNameRight}">
                    </div>
                </div>`;
        }
        
        // הצד השמאלי של השורה (אם קיים)
        if (i + 1 < countries.length) {
            const leftCountryCode = countries[i + 1];
            const displayCodeLeft = leftCountryCode.split('-')[0];
            const countryNameLeft = Object.keys(countriesMapping || {}).find(
                (key) => countriesMapping[key].iso === displayCodeLeft
            ) || leftCountryCode;
            
            modalContent += `
                <div class="country-cell">
                    <div class="country-flag-name">
                        <img src="https://flagcdn.com/24x18/${displayCodeLeft.toLowerCase()}.png" alt="${countryNameLeft}">
                        <span class="country-name">${countryNameLeft}</span>
                    </div>
                </div>`;
        } else {
            // אם אין מדינה שנייה בזוג, הוסף תא ריק
            modalContent += '<div class="country-cell"></div>';
        }
        
        modalContent += '</div>'; // סגירת country-row
    }
    
    modalContent += '</div>'; // סגירת countries-supported-grid
    modalContent += '</div>'; // סגירת details-section
}

        modalContent += '</div>';
        modalContent += `<form method="post" action="${adminAjaxUrl.replace('admin-ajax.php', 'admin-post.php')}">
            <input type="hidden" name="action" value="AdPro_process_package">
            <input type="hidden" name="package_id" value="${packageId}">
            <input type="hidden" name="country" value="${hebrewCountry}">
            <button type="submit" class="buy-now-modal">רכוש עכשיו</button>
        </form>`;

        $('#package-modal-content').html(modalContent);
        $('#package-details-modal').fadeIn();
    });

    $(document).on('click', '.close-modal, .package-modal', function (e) {
        if (e.target === this || $(e.target).hasClass('close-modal')) {
            $('.package-modal').fadeOut();
        }
    });

    // הוספת כפתור סינון צף בתצוגת מובייל
    if (window.innerWidth <= 768 && $('.package-filters').length) {
        $('body').append('<button class="sticky-filter-button">פתח סינון מתקדם</button>');

        $(document).on('click', '.sticky-filter-button', function () {
            toggleMobileFilters();
            if ($('.package-filters').hasClass('filters-open')) {
                $('html, body').animate(
                    { scrollTop: $('.package-filters').offset().top - 10 },
                    200
                );
            }
        });

        $(window).on('scroll', function () {
            if ($('.package-filters').length) {
                const filterSectionTop = $('.package-filters').offset().top;
                const filterSectionHeight = $('.package-filters').outerHeight();
                const scrollPosition = $(window).scrollTop();

                if (
                    scrollPosition + 50 < filterSectionTop ||
                    scrollPosition > filterSectionTop + filterSectionHeight - 100
                ) {
                    $('.sticky-filter-button').addClass('visible');
                } else {
                    $('.sticky-filter-button').removeClass('visible');
                }
            }
        });

        $(window).trigger('scroll');
    }
});