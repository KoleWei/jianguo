define(['jquery', 'bootstrap', 'backend', 'table', 'form'], function ($, undefined, Backend, Table, Form) {

    var Controller = {
        index: function () {
            // 初始化表格参数配置
            Table.api.init({
                extend: {
                    index_url: 'th_zp/index' + location.search,
                    multi_url: 'th_zp/multi',
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
                        {field: 'cust.nickname', title: __('摄影师昵称'), operate:'like', visible: false},
                        {
                            field: 'cust.uname', 
                            title: __('摄影师名称'), 
                            operate:false,
                            formatter: function(val, row, index) {
                                return row['cust']['uname'] || row['cust']['nickname'];
                            }
                        },

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
                        {field: 'style', title: __('类型(不要填写)'), visible: false},
                        {field: 'createtime', title: __('Createtime'), operate:'RANGE', addclass:'datetimerange', formatter: Table.api.formatter.datetime},
                        {
                            field: 'operate', 
                            title: __('Operate'), 
                            table: table, 
                            events: Table.api.events.operate, 
                            formatter: Table.api.formatter.operate,
                            buttons:[{
                                name: 'access',
                                text: __('通过'),
                                classname: 'btn btn-xs btn-info btn-ajax',
                                icon: 'fa',
                                url: 'th_zp/check?status=y',
                                confirm: '作品是否审核通过',
                                success: function (data, ret) {
                                    table.bootstrapTable('refresh');
                                },
                            },{
                                name: 'fail',
                                text: __('拒绝'),
                                classname: 'btn btn-xs btn-info btn-dialog',
                                icon: 'fa',
                                url: 'th_zp/check?status=n',
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
        add: function () {
            Controller.api.bindevent();
        },
        check: function () {
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