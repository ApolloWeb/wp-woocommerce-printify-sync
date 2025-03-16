                                                    <i class="fas fa-reply me-2"></i>
                                                    <?php _e('Reopen', 'wp-woocommerce-printify-sync'); ?>
                                                </a>
                                            </li>
                                        <?php endif; ?>
                                    </ul>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ticket View Modal -->
    <div class="modal fade" id="ticketModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title ticket-subject"></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="ticket-info mb-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <strong><?php _e('Customer:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="ticket-customer"></span>
                            </div>
                            <div class="col-md-6">
                                <strong><?php _e('Created:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="ticket-created"></span>
                            </div>
                            <div class="col-md-6">
                                <strong><?php _e('Status:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="ticket-status"></span>
                            </div>
                            <div class="col-md-6">
                                <strong><?php _e('Order:', 'wp-woocommerce-printify-sync'); ?></strong>
                                <span class="ticket-order"></span>
                            </div>
                        </div>
                    </div>

                    <div class="ticket-messages"></div>

                    <div class="reply-form mt-4">
                        <h6><?php _e('Reply', 'wp-woocommerce-printify-sync'); ?></h6>
                        <form id="replyForm">
                            <div class="mb-3">
                                <select class="form-select" name="template_id">
                                    <option value=""><?php _e('Select Template', 'wp-woocommerce-printify-sync'); ?></option>
                                    <?php foreach ($templates as $id => $template): ?>
                                        <option value="<?php echo esc_attr($id); ?>">
                                            <?php echo esc_html($template['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <textarea class="form-control" name="message" rows="5" required></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i>
                                    <?php _e('Send Reply', 'wp-woocommerce-printify-sync'); ?>
                                </button>
                                <?php if ($ticket['status'] === 'open'): ?>
                                    <button type="button" class="btn btn-outline-success close-and-reply">
                                        <i class="fas fa-check me-1"></i>
                                        <?php _e('Reply & Close', 'wp-woocommerce-printify-sync'); ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>