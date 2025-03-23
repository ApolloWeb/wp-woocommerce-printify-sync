class TicketManager {
    // ...existing code...
    
    private const SLA_LEVELS = [
        'urgent' => 2, // hours
        'high' => 4,   // hours
        'normal' => 8, // hours
        'low' => 24    // hours
    ];
    
    private const PRIORITIES = [
        'urgent' => [
            'label' => 'Urgent',
            'color' => '#dc3545',
            'icon' => 'fa-exclamation-circle'
        ],
        'high' => [
            'label' => 'High',
            'color' => '#fd7e14',
            'icon' => 'fa-arrow-up'
        ],
        'normal' => [
            'label' => 'Normal',
            'color' => '#0dcaf0',
            'icon' => 'fa-minus'
        ],
        'low' => [
            'label' => 'Low',
            'color' => '#6c757d',
            'icon' => 'fa-arrow-down'
        ]
    ];

    protected function createTicketFromEmail(array $email, array $analysis): ?int {
        // ...existing code...
        
        $ticket_data['meta_input'] = array_merge($ticket_data['meta_input'], [
            '_wpwps_ticket_priority' => $analysis['priority'] ?? 'normal',
            '_wpwps_ticket_sla_due' => $this->calculateSLADue($analysis['priority'] ?? 'normal'),
            '_wpwps_ticket_assigned_to' => $this->determineAssignee($analysis),
            '_wpwps_ticket_sentiment' => $analysis['sentiment'] ?? 'neutral'
        ]);
        
        // ...existing code...
    }
    
    private function calculateSLADue(string $priority): string {
        $hours = self::SLA_LEVELS[$priority] ?? self::SLA_LEVELS['normal'];
        return date('Y-m-d H:i:s', strtotime("+{$hours} hours"));
    }
    
    private function determineAssignee(array $analysis): int {
        // Get available agents
        $agents = get_users(['role' => 'wpwps_support_agent']);
        
        // If urgent, assign to senior agent
        if ($analysis['priority'] === 'urgent') {
            $senior_agents = array_filter($agents, function($agent) {
                return get_user_meta($agent->ID, '_wpwps_agent_level', true) === 'senior';
            });
            if (!empty($senior_agents)) {
                return $this->selectLeastBusyAgent($senior_agents);
            }
        }
        
        // Otherwise, use round-robin or workload-based assignment
        return $this->selectLeastBusyAgent($agents);
    }
    
    private function selectLeastBusyAgent(array $agents): int {
        $workloads = [];
        foreach ($agents as $agent) {
            $open_tickets = $this->countAgentOpenTickets($agent->ID);
            $workloads[$agent->ID] = $open_tickets;
        }
        
        return array_search(min($workloads), $workloads);
    }

    public function checkSLABreaches(): void {
        $breached_tickets = get_posts([
            'post_type' => 'support_ticket',
            'meta_query' => [
                [
                    'key' => '_wpwps_ticket_sla_due',
                    'value' => current_time('mysql'),
                    'compare' => '<',
                    'type' => 'DATETIME'
                ],
                [
                    'key' => '_wpwps_ticket_sla_breached',
                    'compare' => 'NOT EXISTS'
                ]
            ],
            'tax_query' => [
                [
                    'taxonomy' => 'ticket_status',
                    'field' => 'slug',
                    'terms' => ['resolved', 'closed'],
                    'operator' => 'NOT IN'
                ]
            ]
        ]);

        foreach ($breached_tickets as $ticket) {
            update_post_meta($ticket->ID, '_wpwps_ticket_sla_breached', current_time('mysql'));
            $this->notifyTeamOfSLABreach($ticket);
        }
    }
    
    private function notifyTeamOfSLABreach($ticket): void {
        $priority = get_post_meta($ticket->ID, '_wpwps_ticket_priority', true);
        $assignee_id = get_post_meta($ticket->ID, '_wpwps_ticket_assigned_to', true);
        
        $message = sprintf(
            'SLA breach for %s priority ticket #%d. Assigned to: %s',
            self::PRIORITIES[$priority]['label'],
            $ticket->ID,
            get_user_by('id', $assignee_id)->display_name
        );
        
        // Notify via email
        $this->email_queue->addToQueue(
            get_option('wpwps_support_team_email'),
            'SLA Breach Alert',
            $message
        );
        
        // Notify via Slack if integrated
        do_action('wpwps_slack_notification', 'sla_breach', [
            'ticket_id' => $ticket->ID,
            'priority' => $priority,
            'message' => $message
        ]);
    }

    public function getTicketMetrics(): array {
        return [
            'sla_compliance' => $this->calculateSLACompliance(),
            'avg_response_time' => $this->calculateAverageResponseTime(),
            'avg_resolution_time' => $this->calculateAverageResolutionTime(),
            'customer_satisfaction' => $this->calculateCustomerSatisfaction(),
            'priority_distribution' => $this->getPriorityDistribution()
        ];
    }
}
