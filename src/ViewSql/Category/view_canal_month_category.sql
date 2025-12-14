CREATE OR REPLACE VIEW view_canal_month_category AS
SELECT
    sp.name AS name,
    sp.user_id AS user_id,
    product_category.category_id AS category_id,
    sale.canal_id,
    IFNULL(SUM(sale.benefit), 0) AS benefit_value,
    IFNULL(SUM(sale.price), 0) AS price_value,
    IFNULL(SUM(sale.nb_product), 0) AS nb_product_value,
    IFNULL(AVG(sale.ursaf), 0) AS ursaf_value,
    IFNULL(SUM(sale.expense), 0) AS expense_value,
    IFNULL(AVG(sale.commission), 0) AS commission_value,
    IFNULL(SUM(sale.time), 0) AS time_value,
    IFNULL((SUM(sale.benefit) / SUM(sale.price)) * 100, 0) AS benefit_pourcent,
    DATE_FORMAT(sale.created_at, '%Y') AS years,
    DATE_FORMAT(sale.created_at, '%m') AS month,
    DATE_FORMAT(sale.created_at, '%Y-%m') AS date_full
FROM
    sale
    LEFT JOIN sales_channel sp ON sale.canal_id = sp.id
    LEFT JOIN sales_product ON sale.id = sales_product.sale_id
    LEFT JOIN product_category ON sales_product.product_id = product_category.product_id
GROUP BY
    sp.name,
    sp.user_id,
    sale.canal_id,
    product_category.category_id,
    DATE_FORMAT(sale.created_at, '%Y'),
    DATE_FORMAT(sale.created_at, '%m'),
    DATE_FORMAT(sale.created_at, '%Y-%m');