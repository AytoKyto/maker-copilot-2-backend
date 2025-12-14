CREATE OR REPLACE VIEW view_best_product_sales_month AS
SELECT
    CONCAT(DATE_FORMAT(MIN(sale.created_at), '%Y-%m'), '-', sales_product.product_id) AS id,
    sale.user_id,
    RANK() OVER (
        PARTITION BY DATE_FORMAT(MIN(sale.created_at), '%Y-%m'), sale.user_id
        ORDER BY COUNT(sales_product.product_id) DESC
        ) AS classement,
    sales_product.product_id,
    product.name AS product_name,
    COUNT(sales_product.product_id) AS nb_product,
    DATE_FORMAT(MIN(sale.created_at), '%Y') AS years,
    DATE_FORMAT(MIN(sale.created_at), '%m') AS month,
    DATE_FORMAT(MIN(sale.created_at), '%Y-%m') AS date_full
FROM
    sales_product
        LEFT JOIN sale ON sales_product.sale_id = sale.id
        LEFT JOIN product ON sales_product.product_id = product.id
WHERE
    sale.created_at IS NOT NULL
GROUP BY
    sales_product.product_id,
    sale.user_id,
    DATE_FORMAT(sale.created_at, '%Y-%m');