CREATE OR REPLACE VIEW view_benefit_month_canal AS
SELECT
    sale.user_id,
    sale.canal_id,
    IFNULL(SUM(sale.nb_product), 0) as nb_product,
    IFNULL(SUM(sale.benefit), 0) AS benefit_value,
    IFNULL(
            (SUM(sale.benefit) / NULLIF(SUM(sale.price), 0)) * 100,
            0
    ) AS benefit_pourcent,
    IFNULL(SUM(sale.price), 0) AS price_value,
    IFNULL(AVG(sale.ursaf), 0) AS ursaf_value,
    IFNULL(SUM(sale.expense), 0) AS expense_value,
    IFNULL(AVG(sale.commission), 0) AS commission_value,
    IFNULL(SUM(sale.time), 0) AS time_value,
    DATE_FORMAT(MAX(sale.created_at), '%Y') AS years,
    DATE_FORMAT(MAX(sale.created_at), '%m') AS month,
    DATE_FORMAT(MAX(sale.created_at), '%Y-%m') AS date_full
FROM
    sale
GROUP BY
    sale.user_id,
    sale.canal_id,
    DATE_FORMAT(sale.created_at, '%Y'),
    DATE_FORMAT(sale.created_at, '%m'),
    DATE_FORMAT(sale.created_at, '%Y-%m');
