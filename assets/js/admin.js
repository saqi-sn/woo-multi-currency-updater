jQuery(document).ready(function($) {
    'use strict';

    // Confirm delete currency
    $('.gmc-delete-currency').on('click', function(e) {
        if (!confirm(gmcData.strings.confirmDelete)) {
            e.preventDefault();
            return false;
        }
    });

    // Confirm remove exclusion
    $('.gmc-remove-exclusion').on('click', function(e) {
        if (!confirm(gmcData.strings.confirmRemove)) {
            e.preventDefault();
            return false;
        }
    });

    // Product search for exclusions
    $('#gmc-search-products').on('click', function() {
        const search = $('#gmc-product-search-input').val();

        if (!search) {
            alert('Please enter a search term');
            return;
        }

        const $button = $(this);
        const $results = $('#gmc-search-results');

        $button.prop('disabled', true).text(gmcData.strings.processing);

        $.ajax({
            url: gmcData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmc_search_products',
                nonce: gmcData.nonce,
                search: search
            },
            success: function(response) {
                if (response.success && response.data.products) {
                    displaySearchResults(response.data.products, $results);
                } else {
                    $results.html('<p class="gmc-error">No products found.</p>');
                }
            },
            error: function() {
                $results.html('<p class="gmc-error">' + gmcData.strings.error + '</p>');
            },
            complete: function() {
                $button.prop('disabled', false).text('Search');
            }
        });
    });

    // Allow search on Enter key
    $('#gmc-product-search-input').on('keypress', function(e) {
        if (e.which === 13) {
            e.preventDefault();
            $('#gmc-search-products').click();
        }
    });

    // Display search results
    function displaySearchResults(products, $container) {
        if (products.length === 0) {
            $container.html('<p class="gmc-info">No products found.</p>');
            return;
        }

        let html = '<table class="wp-list-table widefat fixed striped"><thead><tr>';
        html += '<th>Product Name</th><th>SKU</th><th>Type</th><th>Price</th><th>Action</th>';
        html += '</tr></thead><tbody>';

        products.forEach(function(product) {
            html += '<tr>';
            html += '<td><strong>' + escapeHtml(product.name) + '</strong></td>';
            html += '<td>' + escapeHtml(product.sku || '-') + '</td>';
            html += '<td>' + escapeHtml(product.type) + '</td>';
            html += '<td>' + product.price_html + '</td>';
            html += '<td>';

            if (product.is_excluded) {
                html += '<span class="gmc-badge gmc-badge-info">Already Excluded</span>';
            } else {
                html += '<button type="button" class="button button-small gmc-add-to-exclusions" data-product-id="' + product.id + '">Add to Exclusions</button>';
            }

            html += '</td></tr>';
        });

        html += '</tbody></table>';
        $container.html(html);
    }

    // Add to exclusions
    $(document).on('click', '.gmc-add-to-exclusions', function() {
        const $button = $(this);
        const productId = $button.data('product-id');

        $button.prop('disabled', true).text(gmcData.strings.processing);

        $.ajax({
            url: gmcData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmc_add_exclusion',
                nonce: gmcData.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    $button.replaceWith('<span class="gmc-badge gmc-badge-success">Added!</span>');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    alert(response.data.message || gmcData.strings.error);
                    $button.prop('disabled', false).text('Add to Exclusions');
                }
            },
            error: function() {
                alert(gmcData.strings.error);
                $button.prop('disabled', false).text('Add to Exclusions');
            }
        });
    });

    // Bulk update functionality
    let updateInProgress = false;
    let updateStopped = false;
    let currentOffset = 0;
    let totalProducts = 0;
    let processedCount = 0;

    $('#gmc-start-update').on('click', function() {
        if (updateInProgress) {
            return;
        }

        const isDryRun = $('#gmc-dry-run').is(':checked');
        const confirmMessage = isDryRun
            ? 'Start price update in DRY RUN mode? No prices will be changed.'
            : 'Start ACTUAL price update? This will change product prices. Make sure you have a backup!';

        if (!confirm(confirmMessage)) {
            return;
        }

        updateInProgress = true;
        updateStopped = false;
        currentOffset = 0;
        processedCount = 0;

        $('#gmc-start-update').hide();
        $('#gmc-stop-update').show();
        $('#gmc-update-progress').show();
        $('#gmc-update-log').show();
        $('#gmc-log-content').html('');
        $('#gmc-dry-run').prop('disabled', true);

        logMessage('Starting price update...', 'info');

        if (isDryRun) {
            logMessage('DRY RUN MODE: No prices will be changed', '');
        }

        processBatch(isDryRun);
    });

    $('#gmc-stop-update').on('click', function() {
        if (confirm('Are you sure you want to stop the update process?')) {
            updateStopped = true;
            logMessage('Update process stopped by user', '');
            resetUpdateUI();
        }
    });

    function processBatch(isDryRun) {
        if (updateStopped || !updateInProgress) {
            return;
        }

        $.ajax({
            url: gmcData.ajaxUrl,
            type: 'POST',
            timeout: 30000, // 30 second timeout
            data: {
                action: 'gmc_batch_update',
                nonce: gmcData.nonce,
                offset: currentOffset,
                dry_run: isDryRun ? 'true' : 'false'
            },
            success: function(response) {
                if (response.success && response.data) {
                    const data = response.data;
                    totalProducts = data.total;

                    // Process results
                    if (data.results && data.results.length > 0) {
                        data.results.forEach(function(result) {
                            processResult(result, isDryRun);
                        });
                    }

                    processedCount += data.processed;
                    currentOffset += data.limit;

                    // Update progress
                    updateProgress(processedCount, totalProducts);

                    // Continue if there's more
                    if (data.has_more && !updateStopped) {
                        setTimeout(function() {
                            processBatch(isDryRun);
                        }, 500); // Small delay between batches
                    } else {
                        // Completed
                        logMessage('Update process completed!', 'success');
                        logMessage('Total processed: ' + processedCount + ' products/variations', 'info');
                        resetUpdateUI();
                    }
                } else {
                    logMessage('Error: ' + (response.data?.message || gmcData.strings.error), 'error');
                    resetUpdateUI();
                }
            },
            error: function(xhr, status, error) {
                if (status === 'timeout') {
                    logMessage('Batch timeout - retrying...', '');
                    // Retry the same batch
                    setTimeout(function() {
                        processBatch(isDryRun);
                    }, 1000);
                } else {
                    logMessage('AJAX Error: ' + error, 'error');
                    resetUpdateUI();
                }
            }
        });
    }

    function processResult(result, isDryRun) {
        // Build product identifier
        let productInfo = '';
        if (result.product_name) {
            productInfo = result.product_name;
            if (result.product_id) {
                productInfo += ' (#' + result.product_id + ')';
            }
        } else if (result.product_id) {
            productInfo = 'Product #' + result.product_id;
        }

        if (result.excluded) {
            const msg = productInfo ? productInfo + ' - ' + result.message : result.message;
            logMessage('Skipped (excluded): ' + msg, '');
            return;
        }

        if (result.skipped) {
            const msg = productInfo ? productInfo + ' - ' + result.message : result.message;
            logMessage('Skipped: ' + msg, 'info');
            return;
        }

        if (!result.success) {
            const msg = productInfo ? productInfo + ' - ' + result.message : result.message;
            logMessage('Error: ' + msg, 'error');
            return;
        }

        // Handle variable products (parent with multiple variations)
        if (result.is_variable && result.variations) {
            logMessage('Variable Product: ' + result.product_name + ' (#' + result.product_id + ')', 'info');
            result.variations.forEach(function(variation) {
                const msg = (isDryRun ? '[DRY RUN] ' : '') +
                    '  → ' + variation.variation_name + ': ' +
                    formatPrice(variation.old_price) + ' → ' + formatPrice(variation.new_price) +
                    ' (Base: ' + formatPrice(variation.base_price) + ' ' + variation.currency +
                    ' × ' + variation.exchange_rate + ')';
                logMessage(msg, 'success');
            });
        } else if (result.is_variation) {
            // Single variation (new optimized approach)
            const msg = (isDryRun ? '[DRY RUN] ' : '') +
                result.product_name + ': ' +
                formatPrice(result.old_price) + ' → ' + formatPrice(result.new_price) +
                ' (Base: ' + formatPrice(result.base_price) + ' ' + result.currency +
                ' × ' + result.exchange_rate + ')';
            logMessage(msg, 'success');
        } else {
            // Simple product
            const msg = (isDryRun ? '[DRY RUN] ' : '') +
                result.product_name + ' (#' + result.product_id + '): ' +
                formatPrice(result.old_price) + ' → ' + formatPrice(result.new_price) +
                ' (Base: ' + formatPrice(result.base_price) + ' ' + result.currency +
                ' × ' + result.exchange_rate + ')';
            logMessage(msg, 'success');
        }
    }

    function updateProgress(current, total) {
        const percentage = total > 0 ? Math.round((current / total) * 100) : 0;

        $('#gmc-progress-current').text(current);
        $('#gmc-progress-total').text(total);
        $('.gmc-progress-bar').css('width', percentage + '%');
        $('.gmc-progress-text').text(percentage + '%');
    }

    function logMessage(message, type) {
        const timestamp = new Date().toLocaleTimeString();
        let className = 'gmc-log-entry';

        switch(type) {
            case 'success':
                className += ' gmc-log-success';
                break;
            case 'error':
                className += ' gmc-log-error';
                break;
            case '':
                className += ' gmc-log-';
                break;
            case 'info':
                className += ' gmc-log-info';
                break;
        }

        const $entry = $('<div class="' + className + '"><span class="gmc-log-time">[' + timestamp + ']</span> ' + escapeHtml(message) + '</div>');
        $('#gmc-log-content').prepend($entry);

        // Auto-scroll to top
        $('#gmc-log-content').scrollTop(0);
    }

    function resetUpdateUI() {
        updateInProgress = false;
        $('#gmc-start-update').show();
        $('#gmc-stop-update').hide();
        $('#gmc-dry-run').prop('disabled', false);
    }

    // Manual update for single product
    $(document).on('click', '.gmc-manual-update-btn', function() {
        const $button = $(this);
        const productId = $button.data('product-id');
        const $result = $button.siblings('.gmc-manual-update-result');

        if (!confirm('Update the regular price for this product now?')) {
            return;
        }

        $button.prop('disabled', true).text(gmcData.strings.processing);
        $result.html('');

        $.ajax({
            url: gmcData.ajaxUrl,
            type: 'POST',
            data: {
                action: 'gmc_manual_update',
                nonce: gmcData.nonce,
                product_id: productId
            },
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    let message = '';

                    if (data.is_variable && data.variations) {
                        message = 'Updated ' + data.variations.length + ' variation(s)';
                        data.variations.forEach(function(v) {
                            message += '<br>' + formatPrice(v.old_price) + ' → ' + formatPrice(v.new_price);
                        });
                    } else {
                        message = 'Price updated: ' + formatPrice(data.old_price) + ' → ' + formatPrice(data.new_price);
                    }

                    $result.html('<span class="gmc-success">' + message + '</span>');

                    // Reload page after 2 seconds
                    setTimeout(function() {
                        location.reload();
                    }, 2000);
                } else {
                    $result.html('<span class="gmc-error">' + (response.data?.message || gmcData.strings.error) + '</span>');
                    $button.prop('disabled', false).text('Update Price from Base Price');
                }
            },
            error: function() {
                $result.html('<span class="gmc-error">' + gmcData.strings.error + '</span>');
                $button.prop('disabled', false).text('Update Price from Base Price');
            }
        });
    });

    // Utility functions
    function escapeHtml(text) {
        if (!text) return '';
        const map = {
            '&': '&amp;',
            '<': '&lt;',
            '>': '&gt;',
            '"': '&quot;',
            "'": '&#039;'
        };
        return text.toString().replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    function formatPrice(price) {
        if (!price) return '0.00';
        return parseFloat(price).toFixed(2);
    }
});
