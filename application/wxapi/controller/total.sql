update jg_styles_cust sc set read_num = (
	select sum(read_num) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust
)


update jg_styles_cust sc set ac_zp = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y'
)

update jg_styles_cust sc set has_top = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1
)



update jg_styles_cust sc set read_num = (
	select sum(read_num) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust
), ac_zp = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y'
), has_top = (
	select count(1) from jg_zp zp
	where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1
)


update jg_styles_cust sc set read_num = ( select sum(read_num) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust ), ac_zp = ( select count(1) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.check = 'y' ), has_top = ( select count(1) from jg_zp zp where zp.style = sc.style and zp.cust = sc.cust and zp.is_top = 1 )