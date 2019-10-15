/**
 * Nextcloud Data
 *
 * This file is licensed under the Affero General Public License version 3 or
 * later. See the LICENSE.md file.
 *
 * @author Marcel Scherello <audioplayer@scherello.de>
 * @copyright 2016-2019 Marcel Scherello
 */

'use strict';

if (!OCA.Data) {
    /**
     * @namespace
     */
    OCA.Data = {};
}

/**
 * @namespace OCA.Audioplayer.Sidebar
 */
OCA.Data.Sidebar = {
    sidebar_tabs: {},

    showSidebar: function (evt) {
        var navigationItem = evt.target;
        var datasetId = navigationItem.dataset.id;
        var appsidebar = document.getElementById('app-sidebar');

        if (appsidebar.dataset.id === datasetId) {
            OCA.Data.Sidebar.hideSidebar();
        } else {

            document.getElementById('sidebarTitle').innerHTML = navigationItem.dataset.name;
            //document.getElementById('sidebarMime').innerHTML = navigationItem.dataset.id;

            if (appsidebar.dataset.id === '') {
                $('#sidebarClose').on('click', OCA.Data.Sidebar.hideSidebar);

                OCA.Data.Sidebar.constructTabs();
                document.getElementById('tabHeaderDataset').classList.add('selected');
                OC.Apps.showAppSidebar();
            }

            appsidebar.dataset.id = datasetId;
            document.querySelector('.tabHeader.selected').click();
        }
    },

    registerSidebarTab: function (tab) {
        var id = tab.id;
        this.sidebar_tabs[id] = tab;
    },

    constructTabs: function () {
        var tab = {};

        document.querySelector('.tabHeaders').innerHTML = '';
        document.querySelector('.tabsContainer').innerHTML = '';

        OCA.Data.Sidebar.registerSidebarTab({
            id: 'tabHeaderDataset',
            class: 'tabContainerDataset',
            tabindex: '1',
            name: t('data', 'Dataset'),
            action: OCA.Data.Sidebar.tabContainerDataset,
        });

        OCA.Data.Sidebar.registerSidebarTab({
            id: 'tabHeaderData',
            class: 'tabContainerData',
            tabindex: '2',
            name: t('data', 'Data'),
            action: OCA.Data.Sidebar.tabContainerData,
        });

        //OCA.Data.Sidebar.registerSidebarTab({
        //    id: 'tabHeaderVisualization',
        //    class: 'tabContainerVisualization',
        //    tabindex: '3',
        //    name: t('data', 'Visualization'),
        //    action: OCA.Data.Sidebar.tabContainerVisualization,
        //});

        var items = _.map(OCA.Data.Sidebar.sidebar_tabs, function (item) {
            return item;
        });
        items.sort(OCA.Data.Sidebar.sortByName);

        for (tab in items) {
            var li = $('<li/>').addClass('tabHeader')
                .attr({
                    'id': items[tab].id,
                    'tabindex': items[tab].tabindex
                });
            var atag = $('<a/>').text(items[tab].name);
            atag.prop('title', items[tab].name);
            li.append(atag);
            $('.tabHeaders').append(li);

            var div = $('<div/>').addClass('tab ' + items[tab].class)
                .attr({
                    'id': items[tab].class
                });
            $('.tabsContainer').append(div);
            $('#' + items[tab].id).on('click', items[tab].action);
        }
    },

    hideSidebar: function () {
        document.getElementById('app-sidebar').dataset.id = '';
        OC.Apps.hideAppSidebar();
        document.querySelector('.tabHeaders').innerHTML = '';
        document.querySelector('.tabsContainer').innerHTML = '';
    },

    tabContainerDataset: function () {
        var datasetId = document.getElementById('app-sidebar').dataset.id;

        OCA.Data.Sidebar.resetView();
        document.getElementById('tabHeaderDataset').classList.add('selected');
        document.getElementById('tabContainerDataset').classList.remove('hidden');
        document.getElementById('tabContainerDataset').innerHTML = '<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>';

        $.ajax({
            type: 'GET',
            url: OC.generateUrl('apps/data/dataset/') + datasetId,
            success: function (data) {
                if (data !== 'nodata') {

                    var table = document.getElementById('templateTable').cloneNode(true);
                    document.getElementById('tabContainerDataset').innerHTML = '';
                    document.getElementById('tabContainerDataset').appendChild(table);
                    document.getElementById('tableName').value = data[0][0].name;
                    document.getElementById('tableType').value = data[0][0].type;
                    document.getElementById('tableLink').value = data[0][0].link;
                    document.getElementById('tableVisualization').value = data[0][0].visualization;
                    document.getElementById('tableChart').value = data[0][0].chart;
                    document.getElementById('deleteDatasetButton').addEventListener('click', OCA.Data.Sidebar.handleDeleteDatasetButton);
                    document.getElementById('updateDatasetButton').addEventListener('click', OCA.Data.Sidebar.handleUpdateDatasetButton);
                } else {
                    table = '<div style="margin-left: 2em;" class="get-metadata"><p>' + t('audioplayer', 'No playlist entry') + '</p></div>';
                    document.getElementById('tabContainerDataset').innerHTML = '';
                    document.getElementById('tabContainerDataset').appendChild(table);
                }
                //document.getElementById('tabContainerDataset').appendChild(name);
                //document.getElementById('tabContainerDataset').appendChild(type);
                //document.getElementById('tabContainerDataset').appendChild(visualization);
            }
        });

    },

    tabContainerData: function () {
        var trackid = document.getElementById('app-sidebar').dataset.id;

        OCA.Data.Sidebar.resetView();
        document.getElementById('tabHeaderData').classList.add('selected');
        document.getElementById('tabContainerData').classList.remove('hidden');
        document.getElementById('tabContainerData').innerHTML = '<div style="text-align:center; word-wrap:break-word;" class="get-metadata"><p><img src="' + OC.imagePath('core', 'loading.gif') + '"><br><br></p><p>' + t('audioplayer', 'Reading data') + '</p></div>';

    },

    tabContainerVisualization: function () {
        OCA.Data.Sidebar.resetView();
        document.getElementById('tabHeaderVisualization').classList.add('selected');
        document.getElementById('tabContainerVisualization').classList.remove('hidden');

        var table = document.createElement('div');
        table.style.display = 'table';
        table.classList.add('table');

        var visualization = document.createElement('input');
        visualization.value = data[0][0].visualization;

        var tablerow = document.createElement('div');
        tablerow.style.display = 'table-row';

        var tablekey = document.createElement('div');
        tablekey.innerText = 'Name';

        var name = document.createElement('input');
        name.value = data[0][0].name;

        var tablevalue = document.createElement('div');
        tablevalue.appendChild(name);

        tablerow.appendChild(tablekey);
        tablerow.appendChild(tablevalue);
        table.append(tablerow);

        var tablerow = document.createElement('div');
        tablerow.style.display = 'table-row';

        var tablekey = document.createElement('div');
        tablekey.innerText = 'Type';

        var type = document.createElement('input');
        type.value = data[0][0].type;

        var tablevalue = document.createElement('div');
        tablevalue.appendChild(type);

        tablerow.appendChild(tablekey);
        tablerow.appendChild(tablevalue);
        table.append(tablerow);

        var tablerow = document.createElement('div');
        tablerow.style.display = 'table-row';

        var tablekey = document.createElement('div');
        tablekey.innerText = 'Visualization';

        var visualization = document.createElement('input');
        visualization.value = data[0][0].visualization;

        var tablevalue = document.createElement('div');
        tablevalue.appendChild(visualization);

        tablerow.appendChild(tablekey);
        tablerow.appendChild(tablevalue);
        table.append(tablerow);

        var html = '<div style="margin-left: 2em; background-position: initial;" class="icon-info">';
        html += '<p style="margin-left: 2em;">' + t('audioplayer', 'Available Audio Player Add-Ons:') + '</p>';
        html += '<p style="margin-left: 2em;"><br></p>';
        html += '<a href="https://github.com/rello/audioplayer_editor"  target="_blank" >';
        html += '<p style="margin-left: 2em;">- ' + t('audioplayer', 'ID3 editor') + '</p>';
        html += '</a>';
        html += '<a href="https://github.com/rello/audioplayer_sonos"  target="_blank" >';
        html += '<p style="margin-left: 2em;">- ' + t('audioplayer', 'SONOS playback') + '</p>';
        html += '</a></div>';
        document.getElementById('tabContainerVisualization').innerHTML = html;
    },

    resetView: function () {
        document.querySelector('.tabHeader.selected').classList.remove('selected');
        $('.tab').addClass('hidden');
    },

    sortByName: function (a, b) {
        var aName = a.tabindex;
        var bName = b.tabindex;
        return ((aName < bName) ? -1 : ((aName > bName) ? 1 : 0));
    },

    handleDeleteDatasetButton: function () {
        var id = document.getElementById('app-sidebar').dataset.id
        OCA.Data.Backend.deleteDataset(id);
        OCA.Data.Sidebar.hideSidebar();
    },

    handleUpdateDatasetButton: function () {
        var id = document.getElementById('app-sidebar').dataset.id
        OCA.Data.Backend.updateDataset(id);
    }

}