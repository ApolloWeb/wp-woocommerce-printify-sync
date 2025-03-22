<?php

namespace ApolloWeb\WPWooCommercePrintifySync\Services;

class ProfitCalculator
{
    /**
     * Calculate profit for an order
     */
    public function calculateOrderProfit($order)
    {
        $total_revenue = $order->get_total();
        $total_cost = $this->getOrderTotalCost($order);
        
        return [
            'revenue' => $total_revenue,
            'cost' => $total_cost,
            'profit' => $total_revenue - $total_cost,
            'margin' => $this->calculateMargin($total_revenue, $total_cost),
            'breakdown' => [
                'products_cost' => $this->getOrderProductsCost($order),
                'shipping_cost' => $this->getOrderShippingCost($order),
            ]
        ];
    }

    private function getOrderTotalCost($order)
    {
        return (float) $order->get_meta('_printify_total_cost_with_shipping', true);
    }

    private function getOrderProductsCost($order)
    {
        return (float) $order->get_meta('_printify_total_cost', true);
    }

    private function getOrderShippingCost($order)
    {
        return (float) $order->get_meta('_printify_shipping_cost', true);
    }

    private function calculateMargin($revenue, $cost)
    {
        if ($revenue <= 0) {
            return 0;
        }
        return round((($revenue - $cost) / $revenue) * 100, 2);
    }
}
