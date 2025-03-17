<?php

declare(strict_types=1);

namespace ApolloWeb\WPWooCommercePrintifySync\Emails;

class PrintifyEmailStyles
{
    public function __construct()
    {
        add_filter('woocommerce_email_styles', [$this, 'addCustomStyles']);
    }

    public function addCustomStyles(string $css): string
    {
        $customCss = "
            .printify-tracking-info {
                padding: 15px;
                background: #f8f8f8;
                border-radius: 3px;
                margin-bottom: 20px;
            }
            
            .printify-tracking-info h3 {
                margin: 0 0 10px;
                font-size: 16px;
                font-weight: bold;
                color: #7f54b3;
            }
            
            .printify-tracking-info ul {
                margin: 0;
                padding: 0;
                list-style: none;
            }
            
            .printify-tracking-info li {
                margin-bottom: 5px;
            }
            
            .printify-tracking-info .tracking-number {
                font-weight: bold;
                color: #2196f3;
            }
            
            .printify-tracking-info .carrier {
                color: #666;
            }
            
            .printify-status {
                display: inline-block;
                padding: 5px 10px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
                text-transform: uppercase;
            }
            
            .printify-status.shipped {
                background: #c8e6c9;
                color: #2e7d32;
            }
            
            .printify-status.processing {
                background: #fff3e0;
                color: #ef6c00;
            }
            
            .printify-status.delivered {
                background: #e8f5e9;
                color: #1b5e20;
            }
        ";

        return $css . $customCss;
    }
}