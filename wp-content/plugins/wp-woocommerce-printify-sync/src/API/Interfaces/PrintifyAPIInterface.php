<?php

namespace ApolloWeb\WPWooCommercePrintifySync\API\Interfaces;

interface PrintifyAPIInterface extends 
    ShopManagementInterface, 
    ProductManagementInterface, 
    OrderManagementInterface,
    ConnectionTestInterface
{
    // Empty as we're using interface segregation
}
