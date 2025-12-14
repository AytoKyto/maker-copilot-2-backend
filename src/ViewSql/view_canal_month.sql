CREATE OR REPLACE VIEW view_canal_month AS
SELECT
    sp.name AS name,
    sp.user_id AS user_id,
    sale.canal_id,
    IFNULL(SUM(sale.benefit), 0) AS benefit_value,
    IFNULL(SUM(sale.price), 0) AS price_value,
    IFNULL(SUM(sale.nb_product), 0) AS nb_product_value,
    IFNULL(AVG(sale.ursaf), 0) AS ursaf_value,
    IFNULL(SUM(sale.expense), 0) AS expense_value,
    IFNULL(AVG(sale.commission), 0) AS commission_value,
    IFNULL(SUM(time), 0) AS time_value,
    IFNULL((SUM(sale.benefit) / SUM(price)) * 100, 0) AS benefit_pourcent,
    DATE_FORMAT(sale.created_at, '%Y') AS years,
    DATE_FORMAT(sale.created_at, '%m') AS month,
    DATE_FORMAT(sale.created_at, '%Y-%m') AS date_full
FROM
    sale
    LEFT JOIN sales_channel sp ON sale.canal_id = sp.id
GROUP BY
    sp.name,
    sp.user_id,
    sale.canal_id,
    DATE_FORMAT(sale.created_at, '%Y'),
    DATE_FORMAT(sale.created_at, '%m'),
    DATE_FORMAT(sale.created_at, '%Y-%m');