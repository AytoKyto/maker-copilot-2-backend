CREATE OR REPLACE VIEW view_benefit_month AS
SELECT
    user_id,
    IFNULL(SUM(benefit), 0) AS benefit_value,
    IFNULL(SUM(price), 0) AS price_value,
    IFNULL((SUM(benefit) / SUM(price)) * 100, 0) AS benefit_pourcent,
    DATE_FORMAT(created_at, '%Y') AS years,
    DATE_FORMAT(created_at, '%m') AS month,
    DATE_FORMAT(created_at, '%Y-%m') AS date_full
FROM
    sale
GROUP BY
    user_id,
    DATE_FORMAT(created_at, '%Y'),
    DATE_FORMAT(created_at, '%m'),
    DATE_FORMAT(created_at, '%Y-%m');