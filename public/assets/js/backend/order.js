define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'order/index' + location.search,
                    add_url: 'order/add',
                    edit_url: 'order/edit',
                    del_url: 'order/del',
                    multi_url: 'order/multi',
                    table: 'order',
                }
            });

            var table = $("#table");

            // 初始化表格
            table.bootstrapTable({
                url: $.fn.bootstrapTable.defaults.extend.index_url,
                pk: 'id',
                sortName: 'id',
                columns: [
                    [
                        {field: 'orderno', title: __('Orderno'), operate: 'like'},

                        {field: 'cust.nickname', title: __('销售昵称'), operate: 'like', visible: false},
                        {
                            field: 'cust.uname', title: __('销售名称'), operate: 'like', 
                            formatter: function(val, row, index) {
                                return row['cust']['uname'] || row['cust']['nickname'];
                            }
                        },

                        {field: 'uname', title: __('Uname'), operate: 'like'},
                        {field: 'uphone', title: __('Uphone'), operate: 'like'},
                        {field: 'type', title: __('Type'), searchList: {"sp":__('Type sp'),"ps":__('Type ps')}, formatter: Table.api.formatter.normal},
                        {field: 'ordermoney', title: __('Ordermoney'), operate:'BETWEEN'},
                        {field: 'cbmoney', title: __('Cbmoney'), operate:'BETWEEN'},
                        {field: 'sysmoney', title: __('Sysmoney'), operate:'BETWEEN'},
                        {field: 'status', title: __('Status'), searchList: {"1":__('Status 1'),"2":__('Status 2'),"3":__('Status 3'),"4":__('Status 4'),"5":__('Status 5')}, formatter: Table.api.formatter.status},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'operate', title: __('Operate'), table: table, events: Table.api.events.operate, formatter: Table.api.formatter.operate}
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        add: function () {
            Controller.api.bindevent();
        },
        edit: function () {
            Controller.api.bindevent();
        },
        api: {
            bindevent: function () {
                Form.api.bindevent($("form[role=form]"));
            }
        }
    };
    return Controller;
});