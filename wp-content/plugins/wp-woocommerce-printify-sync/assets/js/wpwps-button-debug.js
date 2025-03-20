/**
 * Button debug helper - loads automatically on admin pages
 */
jQuery(document).ready(function($) {
    // Track button clicks
    $('body').on('click', '.btn, button', function(e) {
        const buttonId = $(this).attr('id') || 'unnamed-button';
        const buttonClass = $(this).attr('class') || 'no-class';
        console.log(`Button clicked: #${buttonId} (${buttonClass})`);
    });
    
    // Check for overlapping elements that might block clicks
    function checkButtonsVisibility() {
        $('.btn, button').each(function() {
            const $btn = $(this);
            const btnId = $btn.attr('id') || 'unnamed-button';
            const position = $btn.offset();
            const zIndex = parseInt($btn.css('z-index'), 10) || 'auto';
            
            // Check if there's any element on top of the button
            const overlapElements = document.elementsFromPoint(
                position.left + $btn.width()/2, 
                position.top + $btn.height()/2
            );
            
            // If this button is not the first element (or second after html/body), it might be obscured
            if (overlapElements.length > 2 && overlapElements[0] !== $btn[0] && overlapElements[1] !== $btn[0]) {
                console.warn(`Button #${btnId} might be obscured by:`, overlapElements[0]);
                
                // Add visual indication for debugging
                $btn.css('border', '2px solid red');
            }
        });
    }
    
    // Run the check after page load
    setTimeout(checkButtonsVisibility, 2000);
    
    // Add a global click handler to debug any clicks
    $(document).on('click', function(e) {
        console.log('Click at:', e.pageX, e.pageY);
        console.log('Element clicked:', e.target);
    });
});
