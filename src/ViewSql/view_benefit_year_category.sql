CREATE OR REPLACE VIEW view_benefit_year_category AS
SELECT
    category.name,
    category.id AS category_id,
    category.user_id,
    COUNT(sales_product.product_id) as nb_product,
    IFNULL(SUM(price.benefit), 0) AS benefit_value,
    IFNULL(SUM(price.price), 0) AS price_value,
    IFNULL(AVG(price.ursaf), 0) AS ursaf_value,
    IFNULL(SUM(price.expense), 0) AS expense_value,
    IFNULL(AVG(price.commission), 0) AS commission_value,
    IFNULL(SUM(price.time), 0) AS time_value,
    IFNULL((SUM(price.benefit) / SUM(price.price)) * 100, 0) AS benefit_pourcent,
    DATE_FORMAT(sale.created_at, '%Y') AS years
FROM
    category category
    LEFT JOIN product_category product_category ON category.id = product_category.category_id
    LEFT JOIN sales_product sales_product ON product_category.product_id = sales_product.product_id
    LEFT JOIN price price ON sales_product.price_id = price.id
    LEFT JOIN sale sale ON sales_product.sale_id = sale.id
GROUP BY
    category.user_id,
    category.id,
    DATE_FORMAT(sale.created_at, '%Y');