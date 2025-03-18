<?php

namespace ApolloWeb\WPWooCommercePrintifySync;

abstract class ProductImportHelper
{
    abstract public function importCategories($productData);
    abstract public function importTags($productData);
    abstract public function importVariants($productData);
    abstract public function uploadImages($productData);
}