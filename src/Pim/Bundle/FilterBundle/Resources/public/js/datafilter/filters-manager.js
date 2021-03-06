define(
    ['underscore', 'oro/datafilter/filters-manager'],
    function(_, FiltersManager) {
        'use strict';

        return FiltersManager.extend({
            addButtonTemplate: _.template(
                '<select id="add-filter-select" multiple>' +
                    '<%  var groups = [_.__("system_filter_group")];' +
                        '_.each(filters, function(filter) {' +
                            'if (filter.group) {' +
                                'var key = filter.groupOrder !== null ? filter.groupOrder : "last";' +
                                'if (_.isUndefined(groups[key])) {' +
                                    'groups[key] = filter.group;' +
                                '} else if (!_.contains(groups, filter.group)) {' +
                                    'groups.push(filter.group);' +
                                '}' +
                            '} else {' +
                                'filter.group = _.__("system_filter_group");' +
                            '} ' +
                       '});' +
                    '%>' +
                    '<% _.each(groups, function (group) { %>' +
                        '<optgroup label="<%= group %>">' +
                            '<% _.each(filters, function (filter, name) { %>' +
                                '<% if (filter.group == group) { %>' +
                                    '<option value="<%= name %>" <% if (filter.enabled) { %>selected<% } %>>' +
                                        '<%= filter.label %>' +
                                    '</option>' +
                                    '<% } %>' +
                            '<% }); %>' +
                        '</optgroup>' +
                    '<% }); %>' +
                '</select>'
            ),

            _initializeSelectWidget: function() {
                FiltersManager.prototype._initializeSelectWidget.apply(this, arguments);

                this.selectWidget.getWidget().addClass('pimmultiselect');
            }
        });
    }
);
