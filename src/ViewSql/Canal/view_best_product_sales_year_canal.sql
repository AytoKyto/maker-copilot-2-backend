CREATE
OR REPLACE VIEW view_best_product_sales_year_canal AS
SELECT sale.user_id,
    DATE_FORMAT(MAX(sale.created_at), '%Y-%m') + '-' +sales_product.product_id as id,
       RANK()                                     OVER (
           PARTITION BY DATE_FORMAT(MAX(sale.created_at), '%Y')
           ORDER BY
               COUNT(sales_product.product_id) DESC
           )                                      AS classement, sales_product.product_id,
       sale.canal_id,
       product.name                            AS product_name,
       COUNT(sales_product.product_id)         AS nb_product,
       DATE_FORMAT(MAX(sale.created_at), '%Y') AS years
FROM sales_product
         LEFT JOIN sale
                   ON sales_product.sale_id = sale.id
         LEFT JOIN product ON sales_product.product_id = product.id
GROUP BY sales_product.product_id,
         sale.user_id,
         sale.canal_id,
         DATE_FORMAT(sale.created_at, '%Y');