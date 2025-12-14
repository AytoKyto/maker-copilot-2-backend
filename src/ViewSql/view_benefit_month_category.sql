CREATE OR REPLACE VIEW view_benefit_month_category AS
SELECT
    category.name,
    category.id AS category_id,
    category.user_id,
    COUNT(sales_product.product_id) AS nb_product,
    IFNULL(SUM(price.benefit), 0) AS benefit_value,
    IFNULL(SUM(price.price), 0) AS price_value,
    IFNULL(AVG(price.ursaf), 0) AS ursaf_value,
    IFNULL(SUM(price.expense), 0) AS expense_value,
    IFNULL(AVG(price.commission), 0) AS commission_value,
    IFNULL(SUM(price.time), 0) AS time_value,
    IFNULL((SUM(price.benefit) / NULLIF(SUM(price.price), 0)) * 100, 0) AS benefit_pourcent,
    DATE_FORMAT(MIN(sale.created_at), '%Y') AS years,
    DATE_FORMAT(MIN(sale.created_at), '%m') AS month,
    DATE_FORMAT(MIN(sale.created_at), '%Y-%m') AS date_full
FROM
    category
        LEFT JOIN product_category ON category.id = product_category.category_id
        LEFT JOIN sales_product ON product_category.product_id = sales_product.product_id
        LEFT JOIN price ON sales_product.price_id = price.id
        LEFT JOIN sale ON sales_product.sale_id = sale.id
WHERE
    sale.created_at IS NOT NULL
GROUP BY
    category.user_id,
    category.id,
    category.name,
    DATE_FORMAT(sale.created_at, '%Y-%m');