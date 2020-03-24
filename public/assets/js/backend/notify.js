define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'notify/index' + location.search,
                    add_url: 'notify/add',
                    edit_url: 'notify/edit',
                    del_url: 'notify/del',
                    multi_url: 'notify/multi',
                    table: 'notify',
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
                        {checkbox: true},
                        {field: 'id', title: __('Id')},
                        {field: 'cust', title: __('Cust')},
                        {field: 'action', title: __('Action'), searchList: {"sys":__('Action sys'),"mg":__('Action mg')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime')},
                        {field: 'type', title: __('Type'), searchList: {"1":__('Type 1'),"2":__('Type 2'),"3":__('Type 3')}, formatter: Table.api.formatter.normal},
                        {field: 'desc', title: __('Desc')},
                        {field: 'cust.nickname', title: __('Cust.nickname')},
                        {field: 'cust.uname', title: __('Cust.uname')},
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