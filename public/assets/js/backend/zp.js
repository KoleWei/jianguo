define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'zp/index' + location.search,
                    add_url: 'zp/add',
                    edit_url: 'zp/edit',
                    del_url: 'zp/del',
                    multi_url: 'zp/multi',
                    table: 'zp',
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
                        {field: 'data', title: __('Data')},
                        {field: 'covorimage', title: __('Covorimage'), events: Table.api.events.image, formatter: Table.api.formatter.image},
                        {field: 'type', title: __('Type'), searchList: {"zp":__('Type zp'),"sp":__('Type sp'),"tx":__('Type tx')}, formatter: Table.api.formatter.normal},
                        {field: 'is_top', title: __('Is_top'), searchList: {"1":__('Is_top 1'),"2":__('Is_top 2')}, formatter: Table.api.formatter.normal},
                        {field: 'check', title: __('Check'), searchList: {"y":__('Check y'),"n":__('Check n'),"t":__('Check t')}, formatter: Table.api.formatter.normal},
                        {field: 'read_num', title: __('Read_num')},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {field: 'cust.nickname', title: __('Cust.nickname')},
                        {field: 'cust.uname', title: __('Cust.uname')},
                        {field: 'styles.name', title: __('Styles.name')},
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