CREATE OR REPLACE VIEW view_best_product_sales_month_category AS
SELECT sale.user_id,
    DATE_FORMAT(MAX(sale.created_at), '%Y-%m') + '-' +sales_product.product_id as id,
       RANK() OVER (
           PARTITION BY DATE_FORMAT(MAX(sale.created_at), '%Y-%m')
           ORDER BY
               COUNT(sales_product.product_id) DESC
           )                                      AS classement,
       sales_product.product_id,
       product_category.category_id,
       product.name                               AS product_name,
       COUNT(sales_product.product_id)            AS nb_product,
       DATE_FORMAT(MAX(sale.created_at), '%Y')    AS years,
       DATE_FORMAT(MAX(sale.created_at), '%m')    AS month,
       DATE_FORMAT(MAX(sale.created_at), '%Y-%m') AS date_full
FROM sales_product
         LEFT JOIN sale ON sales_product.sale_id = sale.id
         LEFT JOIN product_category ON sales_product.product_id = product_category.product_id
         LEFT JOIN product ON sales_product.product_id = product.id
GROUP BY sales_product.product_id,
         sale.user_id,
         product_category.category_id,
         DATE_FORMAT(sale.created_at, '%Y-%m');