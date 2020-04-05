define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'th_star_up/index' + location.search,
                    table: 'star_up',
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
                        {field: 'styles.name', title: __('Styles.name'), operate: 'like'},
                        {field: 'cust.nickname', title: __('Cust.nickname'), operate: 'like'},
                        {field: 'cust.uname', title: __('Cust.uname'), operate: 'like'},
                        {field: 'needstar', title: __('Needstar'), operate:'RANGE'},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons:[{
                                name: 'plist',
                                text: __('作品列表'),
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa',
                                url: 'th_star_up/plist?style={styles.id}&custid={cust.id}'
                            },{
                                name: 'access',
                                text: __('通过'),
                                classname: 'btn btn-xs btn-info btn-ajax',
                                icon: 'fa',
                                url: 'th_star_up/check?status=y',
                                confirm: '是否通过星级',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                            },{
                                name: 'fail',
                                text: __('拒绝'),
                                classname: 'btn btn-xs btn-info btn-ajax',
                                icon: 'fa',
                                url: 'th_star_up/check?status=n',
                                confirm: '是否拒绝星级',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                            }]
                        }
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);
        },
        plist: function () {

            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'th_star_up/plist' + location.search,
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

                        {field: 'styles.name', title: __('Styles.name'), operate: 'like'},

                        {field: 'covorimage', title: __('Covorimage'), operate: false, events: Table.api.events.image, formatter: Table.api.formatter.image},

                        {
                            field: 'data', 
                            title: __('Data'), 
                            operate: false,
                            formatter: function(val, row, index) {
                                switch(row['type']) {
                                    case 'zp':
                                        return '<a target="_brank;" href="' + row['data'] + '"><img class="img-lg img-center" src="' + row['data'] + '"/></a>'
                                    case 'tx':
                                        return '<a target="_brank;" href="https://v.qq.com/x/page/' + row['data'] + '.html">点击查看视频</a>'
                                    default:
                                        break;
                                }
                            }
                        },

                        {field: 'type', title: __('Type'), searchList: {"zp":__('Type zp'),"sp":__('Type sp'),"tx":__('Type tx')}, formatter: Table.api.formatter.normal},
                        {field: 'check', title: __('Check'), searchList: {"y":__('Check y'),"n":__('Check n'),"t":__('Check t')}, formatter: Table.api.formatter.normal},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                    ]
                ]
            });

            // 为表格绑定事件
            Table.api.bindevent(table);

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