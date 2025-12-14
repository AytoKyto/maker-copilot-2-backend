CREATE OR REPLACE VIEW view_benefit_year_client AS
SELECT
    sale.user_id,
    sales_product.client_id,
    COUNT(sales_product.product_id) as nb_product,
    IFNULL(SUM(price.benefit), 0) AS benefit_value,
    IFNULL(SUM(price.price), 0) AS price_value,
    IFNULL(AVG(price.ursaf), 0) AS ursaf_value,
    IFNULL(SUM(price.expense), 0) AS expense_value,
    IFNULL(AVG(price.commission), 0) AS commission_value,
    IFNULL(SUM(price.time), 0) AS time_value,
    IFNULL(
        (SUM(price.benefit) / NULLIF(SUM(price.price), 0)) * 100,
        0
    ) AS benefit_pourcent,
    DATE_FORMAT(sale.created_at, '%Y') AS years
FROM
    sales_product
    LEFT JOIN sale ON sales_product.sale_id = sale.id
    LEFT JOIN price ON sales_product.price_id = price.id
GROUP BY
    sale.user_id,
    sales_product.client_id,
    DATE_FORMAT(sale.created_at, '%Y');