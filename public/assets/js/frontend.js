jQuery(document).ready(function($) {
	

$('.filter-mobile-toggle').on('click', function() {
    $('.package-filters').toggleClass('filters-open');
    
    // שינוי טקסט הכפתור
    if ($('.package-filters').hasClass('filters-open')) {
        $(this).text('סגור סינון');
    } else {
        $(this).text('פתח סינון מתקדם');
    }
});

    // סינון חבילות

if ($('.packages-list').length) {
    
    function filterPackages() {
        var dataFilter = $('#data-filter').val();
        var durationFilter = $('#duration-filter').val();
        var priceFilter = $('#price-filter').val();
        
        // עבור על כל החבילות
        $('.package').each(function() {
            var $package = $(this);
            var dataText = $package.find('.data').text();
            var durationText = $package.find('.validity').text();
            
            // חלץ את כמות הנתונים (GB)
            var dataMatch = dataText.match(/(\d+(\.\d+)?)\s*GB/i);
            var dataAmount = dataMatch ? parseFloat(dataMatch[1]) : 0;
            
            // חלץ את משך הזמן (ימים)
            var durationMatch = durationText.match(/(\d+)\s*ימים/i);
            var durationDays = durationMatch ? parseInt(durationMatch[1]) : 0;
            
            var showByData = dataFilter === 'all';
            var showByDuration = durationFilter === 'all';
            
            // בדיקת סינון לפי נתונים
            if (!showByData) {
                if (dataFilter === '1-5' && dataAmount >= 1 && dataAmount <= 5) showByData = true;
                else if (dataFilter === '5-10' && dataAmount > 5 && dataAmount <= 10) showByData = true;
                else if (dataFilter === '10-20' && dataAmount > 10 && dataAmount <= 20) showByData = true;
                else if (dataFilter === '20-50' && dataAmount > 20 && dataAmount <= 50) showByData = true;
                else if (dataFilter === '50+' && dataAmount > 50) showByData = true;
            }
            
            // בדיקת סינון לפי זמן
            if (!showByDuration) {
                if (durationFilter === '1-7' && durationDays >= 1 && durationDays <= 7) showByDuration = true;
                else if (durationFilter === '7-14' && durationDays > 7 && durationDays <= 14) showByDuration = true;
                else if (durationFilter === '14-30' && durationDays > 14 && durationDays <= 30) showByDuration = true;
                else if (durationFilter === '30-90' && durationDays > 30 && durationDays <= 90) showByDuration = true;
                else if (durationFilter === '90+' && durationDays > 90) showByDuration = true;
            }
            
            // הצג או הסתר את החבילה
            if (showByData && showByDuration) {
                $package.removeClass('hidden-package');
            } else {
                $package.addClass('hidden-package');
            }
        });
        
        // בדוק אם אין חבילות מוצגות לאחר סינון
        if ($('.package:not(.hidden-package)').length === 0) {
            if ($('.no-filtered-packages').length === 0) {
                $('.packages-list').append('<div class="no-filtered-packages"><p>לא נמצאו חבילות העונות לקריטריוני הסינון.</p></div>');
            }
        } else {
            $('.no-filtered-packages').remove();
        }
    }
    
    // פונקציה חדשה למיון לפי מחיר
    function sortPackagesByPrice() {
        var sortDirection = $('#price-filter').val();
        
        if (sortDirection === 'all') {
            return; // אין צורך במיון
        }
        
        var $packagesContainer = $('.packages-list');
        var $packages = $packagesContainer.children('.package').get();
        
        $packages.sort(function(a, b) {
            var priceA = parseFloat($(a).find('.price').text().replace(/[^\d.]/g, ''));
            var priceB = parseFloat($(b).find('.price').text().replace(/[^\d.]/g, ''));
            
            if (isNaN(priceA)) priceA = 0;
            if (isNaN(priceB)) priceB = 0;
            
            if (sortDirection === 'low-to-high') {
                return priceA - priceB;
            } else {
                return priceB - priceA;
            }
        });
        
        // החלף את הסדר בדף
        $.each($packages, function(i, item) {
            $packagesContainer.append(item);
        });
    }
    
    // אירועי שינוי בסינון
    $('#data-filter, #duration-filter').on('change', filterPackages);
    
    // אירוע שינוי במיון לפי מחיר
    $('#price-filter').on('change', sortPackagesByPrice);
    
    // איפוס סינון
    $('#reset-filters').on('click', function() {
        $('#data-filter, #duration-filter').val('all');
        $('#price-filter').val('all');
        $('.package').removeClass('hidden-package');
        $('.no-filtered-packages').remove();
        
        // רענן את סדר החבילות המקורי (אפשרי רק אם שומרים סדר מקורי)
        // לצורך כך צריך להוסיף נתון מקורי לכל חבילה או לרענן את הדף
    });
}


    // חיפוש מדינות
    var countries = AdPro_esim_ajax.countries;
    var $searchInput = $('#country-search');
    var $suggestionsContainer = $('#country-suggestions');
    
    $searchInput.on('input', function() {
        var input = $(this).val().trim().toLowerCase(); // התעלמות מרגישות לאותיות
        
        if (input.length === 0) {
            $suggestionsContainer.empty().hide();
            return;
        }
        
        var matches = [];
        Object.keys(countries).forEach(function(hebrew) {
            if (hebrew.toLowerCase().indexOf(input) !== -1) {
                matches.push({
                    hebrew: hebrew,
                    iso: countries[hebrew].iso,
                    slug: countries[hebrew].english.toLowerCase()
                });
            }
        });
        
        matches.sort(function(a, b) {
            var aStartsWith = a.hebrew.toLowerCase().indexOf(input) === 0;
            var bStartsWith = b.hebrew.toLowerCase().indexOf(input) === 0;
            
            if (aStartsWith && !bStartsWith) return -1;
            if (!aStartsWith && bStartsWith) return 1;
            return a.hebrew.localeCompare(b.hebrew);
        });
        
        if (matches.length > 0) {
            var html = '';
            matches.forEach(function(match) {
                html += '<div class="suggestion" data-country="' + match.slug + '">' +
                    '<img src="https://flagcdn.com/16x12/' + match.iso.toLowerCase() + '.png" alt="' + match.hebrew + '"> ' +
                    '<span>' + match.hebrew + '</span></div>';
            });
            $suggestionsContainer.html(html).show();
        } else {
            $suggestionsContainer.html('<div class="no-results">לא נמצאו תוצאות</div>').show();
        }
    });
    
    $(document).on('click', '.suggestion', function() {
        var slug = $(this).data('country');
        window.location.href = AdPro_esim_ajax.site_url + '/esim/' + slug;
    });
    
    $(document).on('click', function(e) {
        if (!$(e.target).closest('.search-box').length) {
            $suggestionsContainer.hide();
        }
    });
    
    $searchInput.on('keydown', function(e) {
        var $suggestions = $('.suggestion');
        var $selected = $('.suggestion.selected');
        var $current;
        
        if (e.keyCode === 40) { // חץ למטה
            if ($selected.length === 0 || $selected.is(':last-child')) {
                $current = $suggestions.first();
            } else {
                $current = $selected.next();
            }
            $suggestions.removeClass('selected');
            $current.addClass('selected');
            // גלילה אוטומטית להצעה שנבחרה
            $current[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return false;
        }
        
        if (e.keyCode === 38) { // חץ למעלה
            if ($selected.length === 0 || $selected.is(':first-child')) {
                $current = $suggestions.last();
            } else {
                $current = $selected.prev();
            }
            $suggestions.removeClass('selected');
            $current.addClass('selected');
            $current[0].scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            return false;
        }
        
        if (e.keyCode === 13 && $selected.length > 0) { // Enter
            e.preventDefault();
            $selected.click();
        }
    });

    // פתיחת המודאל של פרטי החבילה בלחיצה על החבילה
    $(document).on('click', '.package-clickable', function(e) {
        // מניעת הפעלה אם לחצו על הכפתור "רכוש עכשיו"
        if ($(e.target).hasClass('buy-now') || $(e.target).closest('.buy-now').length) {
            return;
        }
        
        // קבלת מזהה החבילה
        var packageId = $(this).data('package-id');
        var packageDetails = null;
        
        // חיפוש נתוני החבילה ממשתנה גלובלי
        for (var i = 0; i < packageData.length; i++) {
            if (packageData[i].productId === packageId) {
                packageDetails = packageData[i];
                break;
            }
        }
        
        if (!packageDetails) {
            return;
        }
        
        // בניית תוכן המודאל
        var modalContent = '';
        
        // כותרת החבילה
        var packageTitle = packageDetails.productId;
        if (packageDetails.productDetails) {
            for (var i = 0; i < packageDetails.productDetails.length; i++) {
                if (packageDetails.productDetails[i].name === 'PLAN_TITLE' && packageDetails.productDetails[i].value) {
                    packageTitle = packageDetails.productDetails[i].value;
                    break;
                }
            }
        }
        
        // חילוץ נתוני חבילה רלוונטיים
        var dataLimit = '';
        var dataUnit = '';
        var validityDays = '';
        
        if (packageDetails.productDetails) {
            for (var i = 0; i < packageDetails.productDetails.length; i++) {
                var detail = packageDetails.productDetails[i];
                
                if (detail.name === 'PLAN_DATA_LIMIT' && detail.value) {
                    dataLimit = detail.value;
                }
                
                if (detail.name === 'PLAN_DATA_UNIT' && detail.value) {
                    dataUnit = detail.value;
                }
                
                if (detail.name === 'PLAN_VALIDITY' && detail.value) {
                    validityDays = Math.round(parseInt(detail.value) / 24);
                }
            }
        }
        
        // בניית כותרת מותאמת אישית - החלק החדש!
        var customTitle = '';
        var countriesCount = packageDetails.countries ? packageDetails.countries.length : 0;

        // הוספת שם המדינה
        if (hebrewCountry) {
            customTitle += "חבילת גלישה ל" + hebrewCountry;
            
            // הוספת מספר מדינות נוספות אם יש יותר ממדינה אחת
            if (countriesCount > 1) {
                var additionalCountries = countriesCount - 1;
                customTitle += " ועוד " + additionalCountries + " מדינות";
            }
        } else {
            // כותרת עם רק מספר מדינות אם אין מדינה ספציפית
            customTitle += "חבילת גלישה ל-" + countriesCount + " מדינות";
        }

        // הוספת נפח הנתונים
        if (dataLimit && dataUnit) {
            customTitle += " עם " + dataLimit + " " + dataUnit;
        }

        // הוספת תקופת התוקף
        if (validityDays) {
            customTitle += " למשך " + validityDays + " ימים";
        }

        modalContent += '<h2>' + customTitle + '</h2>';
        
        // מידע כללי
        modalContent += '<div class="modal-details">';
        
        // מידע בסיסי
        modalContent += '<div class="details-section">';
        modalContent += '<h4>פרטי חבילה</h4>';
        
        // מחיר
        modalContent += '<p class="modal-price">' + packageDetails.retailPrice + ' ' + packageDetails.currencyCode + '</p>';
        
        // נתונים נוספים
        if (dataLimit && dataUnit) {
            modalContent += '<p><strong>נתונים:</strong> ' + dataLimit + ' ' + dataUnit + '</p>';
        }
        
        if (validityDays) {
            modalContent += '<p><strong>תוקף:</strong> ' + validityDays + ' ימים</p>';
        }
        
        if (packageDetails.providerName) {
            modalContent += '<p><strong>ספק:</strong> ' + packageDetails.providerName + '</p>';
        }
        
        // תיאור מורחב
        modalContent += '<div class="package-description">';
        var description = "חבילת גלישה ל" + hebrewCountry;
        if (dataLimit && validityDays) {
            description += " עם " + dataLimit + " " + dataUnit + " למשך " + validityDays + " ימים.";
        }
        modalContent += '<p>' + description + '</p>';
        modalContent += '</div>';
        
        modalContent += '</div>'; // סיום details-section
        
        // רשימת מדינות נתמכות
        if (packageDetails.countries && packageDetails.countries.length > 0) {
            modalContent += '<div class="details-section">';
            modalContent += '<h4>כל המדינות הנתמכות (' + packageDetails.countries.length + ')</h4>';
            modalContent += '<div class="all-countries-grid">';
            
            packageDetails.countries.forEach(function(countryCode) {
                // טיפול בקודי מדינה מיוחדים
                var displayCode = countryCode;
                if (countryCode.indexOf('-') !== -1) {
                    displayCode = countryCode.split('-')[0];
                }
                
                // ניסיון להמיר קוד ISO לשם מדינה בעברית
                var countryName = countryCode; // ברירת מחדל
                
                // אנחנו מחפשים במשתנה countriesMapping שהועבר מ-PHP
                for (var hebrew in countriesMapping) {
                    if (countriesMapping[hebrew].iso === displayCode) {
                        countryName = hebrew;
                        break;
                    }
                }
                
                modalContent += '<div class="country-item">';
                modalContent += '<img src="https://flagcdn.com/24x18/' + displayCode.toLowerCase() + '.png" alt="' + countryCode + '">';
                modalContent += '<span>' + countryName + '</span>';
                modalContent += '</div>';
            });
            
            modalContent += '</div>'; // סיום all-countries-grid
            modalContent += '</div>'; // סיום details-section
        }
        
        modalContent += '</div>'; // סיום modal-details
        
        // כפתור רכישה
        modalContent += '<form method="post" action="' + adminAjaxUrl.replace('admin-ajax.php', 'admin-post.php') + '">';
modalContent += '<input type="hidden" name="action" value="AdPro_process_package">';
modalContent += '<input type="hidden" name="package_id" value="' + packageDetails.productId + '">';
modalContent += '<input type="hidden" name="country" value="' + hebrewCountry + '">';
modalContent += '<button type="submit" class="buy-now-modal">רכוש עכשיו</button>';
modalContent += '</form>';
        modalContent += '</div>';
        
        // הזנת התוכן למודאל והצגתו
        $('#package-modal-content').html(modalContent);
        $('#package-details-modal').fadeIn();
    });

    // סגירת המודאל
    $(document).on('click', '.close-modal', function() {
        $(this).closest('.package-modal').fadeOut();
    });

    // סגירה בלחיצה מחוץ למודאל
    $(document).on('click', '.package-modal', function(e) {
        if ($(e.target).hasClass('package-modal')) {
            $(this).fadeOut();
        }
    });
});