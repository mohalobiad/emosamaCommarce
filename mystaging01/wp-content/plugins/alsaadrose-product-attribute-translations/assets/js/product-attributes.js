/* global aspatnProductAttributes, woocommerce_admin_meta_boxes */
jQuery(function ($) {
    // نتأكد إن الأوبجكت موجود
    if (typeof window.aspatnProductAttributes === 'undefined') {
        window.aspatnProductAttributes = { names: {}, values: {} };
    }
    if (!aspatnProductAttributes.names) {
        aspatnProductAttributes.names = {};
    }
    if (!aspatnProductAttributes.values) {
        aspatnProductAttributes.values = {};
    }

    // نفس فكرة aspatn_sanitize_attribute_slug تقريباً (للـ ASCII)
    function makeSlug(raw) {
        var slug = $.trim(raw || '');
        if (!slug) return '';

        slug = slug.toLowerCase();

        // global attribute مثل pa_color
        if (slug.indexOf('pa_') === 0) {
            return slug.replace(/[^a-z0-9_]/g, '_');
        }

        slug = slug.replace(/[^a-z0-9\s_-]/g, '');
        slug = slug.replace(/\s+/g, '-');
        slug = slug.replace(/-+/g, '-');

        return slug;
    }

    // إضافة / تحديث الحقول العربية لسطر واحد
    function ensureArabicFieldsForRow($row, overrides) {
        var rawName = $row.find('input.attribute_name').val();
        var slug    = makeSlug(rawName);
        if (!slug) return;

        overrides = overrides || {};

        var $nameCell   = $row.find('td.attribute_name');
        var $valuesCell = $row.find('textarea[name^="attribute_values["]').closest('td');

        if (!$nameCell.length || !$valuesCell.length) return;

        var nameFieldSelector   = 'input[name^="attribute_names_ar["]';
        var valuesFieldSelector = 'textarea[name^="attribute_values_ar["]';

        var $nameAr   = $nameCell.find(nameFieldSelector);
        var $valuesAr = $valuesCell.find(valuesFieldSelector);

        var fromCacheName =
            (overrides.names && overrides.names[slug]) ||
            aspatnProductAttributes.names[slug] ||
            '';

        var fromCacheValues = null;

        if (overrides.values && overrides.values[slug]) {
            fromCacheValues = overrides.values[slug];
        } else if (aspatnProductAttributes.values[slug]) {
            fromCacheValues = aspatnProductAttributes.values[slug];
        }

        var valuesString = '';
        if (fromCacheValues && typeof fromCacheValues === 'object') {
            valuesString = Object.values(fromCacheValues).join(' | ');
        }

        // Arabic name
        if (!$nameAr.length) {
            var indexMatch = $nameCell.find('input.attribute_name').attr('name').match(/\[(\d+)\]/);
            var index      = indexMatch ? indexMatch[1] : '';

            var nameHtml =
                '<div class="aspatn-field aspatn-name">' +
                    '<label>Arabic name (optional):</label>' +
                    '<input type="text" name="attribute_names_ar[' + index + ']" value="' + _.escape(fromCacheName) + '" />' +
                '</div>';

            $nameCell.append(nameHtml);
            $nameAr = $nameCell.find(nameFieldSelector);
        } else if (fromCacheName) {
            $nameAr.val(fromCacheName);
        }

        // Arabic values
        if (!$valuesAr.length) {
            var indexMatch2 = $valuesCell.find('textarea[name^="attribute_values["]').attr('name').match(/\[(\d+)\]/);
            var index2      = indexMatch2 ? indexMatch2[1] : '';

            var valuesHtml =
                '<div class="aspatn-field aspatn-values">' +
                    '<label>Arabic value(s) (use | to separate options):</label>' +
                    '<textarea name="attribute_values_ar[' + index2 + ']" rows="3">' + _.escape(valuesString || '') + '</textarea>' +
                '</div>';

            $valuesCell.append(valuesHtml);
            $valuesAr = $valuesCell.find(valuesFieldSelector);
        } else if (valuesString) {
            $valuesAr.val(valuesString);
        }
    }

    function refreshArabicFields(overrides) {
        $('.product_attributes .woocommerce_attribute').each(function () {
            ensureArabicFieldsForRow($(this), overrides);
        });
    }

    // أول تحميل للصفحة
    refreshArabicFields();

    // لما يضيف attribute جديد عن طريق زر + (WooCommerce event)
    $(document.body).on('woocommerce_added_attribute', function () {
        refreshArabicFields();
    });

    // نراقب أي تغيير كبير في .product_attributes (مثلاً لما WooCommerce يبدل الـ HTML)
    var attrContainer = null;
    var mo = null;

    function attachMutationObserver() {
        if (!window.MutationObserver) return;

        var newContainer = document.querySelector('.product_attributes');

        // لو تم تبديل العنصر بعد حفظ الـ attributes نحتاج نعيد الربط
        if (!newContainer || newContainer === attrContainer) {
            return;
        }

        // نفصل المراقب القديم قبل الربط الجديد
        if (mo) {
            mo.disconnect();
        }

        attrContainer = newContainer;
        mo            = new MutationObserver(function (mutations) {
            var needsRefresh = false;

            mutations.forEach(function (m) {
                if (m.addedNodes && m.addedNodes.length) {
                    needsRefresh = true;
                }
            });

            if (needsRefresh) {
                refreshArabicFields();
            }
        });

        mo.observe(attrContainer, { childList: true, subtree: true });
    }

    // مراقبة أولية
    attachMutationObserver();

    // --------- حل مشكلة Save attributes: إعادة تحميل القيم العربية من الـ DB ---------
    function fetchTranslationsAndRefresh() {
        // نتأكد إن الأوبجكت تبع WooCommerce admin موجود
        if (typeof window.woocommerce_admin_meta_boxes === 'undefined') {
            attachMutationObserver();
            refreshArabicFields();
            return;
        }

        $.post(
            woocommerce_admin_meta_boxes.ajax_url,
            {
                action: 'aspatn_get_attribute_translations',
                product_id: woocommerce_admin_meta_boxes.post_id
            }
        )
        .done(function (response) {
            if (response && response.success && response.data) {
                aspatnProductAttributes.names  = response.data.names  || {};
                aspatnProductAttributes.values = response.data.values || {};
            }

            attachMutationObserver();
            refreshArabicFields();
        })
        .fail(function () {
            attachMutationObserver();
            refreshArabicFields();
        });
    }

    function extractAction(settings) {
        if (!settings) return '';

        var data = settings.data;

        if (data instanceof FormData) {
            var actionFromFormData = '';
            data.forEach(function (value, key) {
                if (key === 'action') {
                    actionFromFormData = value;
                }
            });

            if (actionFromFormData) {
                return actionFromFormData;
            }
        }

        if ($.isPlainObject(data) && data.action) {
            return data.action;
        }

        if (typeof data === 'string') {
            var match = data.match(/(?:^|&)action=([^&]+)/);
            if (match && match[1]) {
                return decodeURIComponent(match[1].replace(/\+/g, ' '));
            }
        }

        if (typeof settings.url === 'string') {
            var urlMatch = settings.url.match(/(?:[?&])action=([^&]+)/);
            if (urlMatch && urlMatch[1]) {
                return decodeURIComponent(urlMatch[1].replace(/\+/g, ' '));
            }
        }

        return '';
    }

    function runAfterAttributesHtmlIsReplaced(callback) {
        var container = document.querySelector('.product_attributes');

        if (!container || !window.MutationObserver) {
            setTimeout(callback, 0);
            return;
        }

        var fired = false;
        var observer = new MutationObserver(function (mutations) {
            var replaced = mutations.some(function (m) {
                return (m.addedNodes && m.addedNodes.length) || (m.removedNodes && m.removedNodes.length);
            });

            if (replaced) {
                fired = true;
                observer.disconnect();
                setTimeout(callback, 0);
            }
        });

        observer.observe(container, { childList: true, subtree: true });

        setTimeout(function () {
            if (fired) return;
            observer.disconnect();
            callback();
        }, 300);
    }

    $(document).ajaxPrefilter(function (options, originalOptions, jqXHR) {
        var action = extractAction(originalOptions) || extractAction(options);

        if (action !== 'woocommerce_save_attributes') {
            return;
        }

        var fetched = false;
        var scheduleFetch = function () {
            if (fetched) return;
            fetched = true;

            // ننتظر لغاية ما WooCommerce يستبدل HTML الخصائص، ثم نجلب الترجمات
            runAfterAttributesHtmlIsReplaced(fetchTranslationsAndRefresh);
        };

        // نضمن أننا نتشبّث بالـ Promise حتى بعد تهيئة WooCommerce للـ success
        jqXHR.done(scheduleFetch);

        if (typeof options.success === 'function') {
            var originalSuccess = options.success;
            options.success = function () {
                var result = originalSuccess.apply(this, arguments);
                scheduleFetch();
                return result;
            };
        }
    });

    $(document.body).on('woocommerce_attributes_saved', function () {
        fetchTranslationsAndRefresh();
    });
});