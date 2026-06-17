---
estimated_steps: 1
estimated_files: 5
skills_used: []
---

# T03: Fechar ordem de servico em venda

Implement close-into-sale behavior without nested transactions. The repository/controller must build sale input from service-order items and posted payment data, call the existing SalesRepository sale completion path, link the sale to the order, mark status closed, set closed_at and redirect to the sale detail. Tests must prove sale/payment creation, order-sale linkage, product stock decrement only at closure, service items do not create stock movement and payment validation failures keep the order open.

## Inputs

- `src/Sales/SalesRepository.php`
- `src/Sales/SalesValidator.php`
- `src/ServiceOrders/ServiceOrderRepository.php`
- `src/Controllers/ServiceOrderController.php`

## Expected Output

- `src/ServiceOrders/ServiceOrderRepository.php`
- `src/Controllers/ServiceOrderController.php`
- `templates/service-orders/show.php`
- `tests/ServiceOrders/ServiceOrderRepositoryTest.php`
- `tests/Feature/ServiceOrderFlowTest.php`

## Verification

composer test

## Observability Impact

Closed orders show sale linkage and closed timestamp; payment errors render on the order detail instead of silently failing.
