/* User Lists Query Result = 1 */
select l.id, l.name from users_lists as ul join lists as l on ul.list_id = l.id;

/* List Products Query Param = 1 */
select p.id, p.name, p.price, c.name as 'category_name' from list_products as lp join products as p on lp.product_id = p.id join schopi_api.categories c on c.id = p.category_id where lp.list_id = 1;

/* List Products */
select p.id, p.name, p.price, lp.count as 'price' from list_products as lp join products as p on lp.product_id = p.id where lp.list_id = 1;

/* List Products Count * Price Row by Row*/
select p.id, p.name, p.price, lp.count as 'count', (p.price * lp.count) as 'total' from list_products as lp join products as p on lp.product_id = p.id where lp.list_id = 1;