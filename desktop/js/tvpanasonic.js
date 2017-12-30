
/* This file is part of Jeedom.
 *
 * Jeedom is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Jeedom is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Jeedom. If not, see <http://www.gnu.org/licenses/>.
 */



/*
 * Fonction pour l'ajout de commande, appellé automatiquement par plugin.template
 */

/* Fonction appelé pour mettre l'affichage du tableau des commandes de votre eqLogic
 * _cmd: les détails de votre commande
 */
/* global jeedom */

$("#table_cmd").sortable({axis: "y", cursor: "move", items: ".cmd", placeholder: "ui-state-highlight", tolerance: "intersect", forcePlaceholderSize: true});


function addCmdToTable(_cmd) {
    if (!isset(_cmd)) {
        var _cmd = {configuration: {}};
    }
    var tr = '<tr class="cmd" data-cmd_id="' + init(_cmd.id) + '">';
    tr += '<td>';
    tr += '<div class="row">';
    tr += '<div class="col-sm-6">';
    tr += '<a class="cmdAction btn btn-default btn-sm" data-l1key="chooseIcon"><i class="fa fa-flag"></i> Icône</a>';
    tr += '<span class="cmdAttr" data-l1key="display" data-l2key="icon" style="margin-left : 10px;"></span>';
    tr += '</div>';
    tr += '<div class="col-sm-6">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="name">';
    tr += '</div>';
    tr += '</div>';
    tr += '<select class="cmdAttr form-control tooltips input-sm" data-l1key="value" style="display : none;margin-top : 5px;" title="La valeur de la commande vaut par défaut la commande">';
    tr += '<option value="">Aucune</option>';
    tr += '</select>';
    tr += '</td>';
    tr += '<td class="expertModeVisible">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="id" style="display : none;">';
    tr += '<span class="type" type="' + init(_cmd.type) + '">' + jeedom.cmd.availableType() + '</span>';
    tr += '<span class="subType" subType="' + init(_cmd.subType) + '"></span>';
    tr += '</td>';
    tr += '<td class="expertModeVisible"><input class="cmdAttr form-control input-sm" data-l1key="logicalId" value="0" style="width : 70%; display : inline-block;" placeholder="{{Commande}}"><br/>';

    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="id" placeholder="{{ID}}" style="margin-top : 5px;margin-right:2px;width:24%;display:inline-block;">';
    tr += ' <input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="group" style="width : 20%; display : inline-block;" placeholder="{{Groupe}}">';

    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateValue" placeholder="{{Valeur retour d\'état}}" style="width : 20%; display : inline-block;margin-top : 5px;margin-right : 5px;">';
    tr += '<input class="cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="returnStateTime" placeholder="{{Durée avant retour d\'état (min)}}" style="width : 20%; display : inline-block;margin-top : 5px;margin-right : 5px;">';
    tr += '</td>';
    tr += '<td>';
    tr += '<span><input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="isHistorized" data-size="mini" data-label-text="{{Historiser}}" /></span> ';
    tr += '<span><input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="isVisible" data-size="mini" data-label-text="{{Afficher}}" checked/></span> ';
    tr += '<span class="expertModeVisible"><input type="checkbox" class="cmdAttr bootstrapSwitch" data-l1key="display" data-label-text="{{Inverser}}" data-size="mini" data-l2key="invertBinary" /></span> ';
    tr += '</td>';
    tr += '<td>';
    tr += '<select class="cmdAttr form-control tooltips input-sm" data-l1key="configuration" data-l2key="updateCmdId" style="display : none;margin-top : 5px;" title="Commande d\'information à mettre à jour">';
    tr += '<option value="">Aucune</option>';
    tr += '</select>';
    tr += '<input class="tooltips cmdAttr form-control input-sm" data-l1key="configuration" data-l2key="updateCmdToValue" placeholder="Valeur de l\'information" style="display : none;margin-top : 5px;">';
    tr += '<input class="cmdAttr form-control tooltips input-sm" data-l1key="unite"  style="width : 100px;" placeholder="Unité" title="Unité">';
    tr += '<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="minValue" placeholder="Min" title="Min"> ';
    tr += '<input class="tooltips cmdAttr form-control input-sm expertModeVisible" data-l1key="configuration" data-l2key="maxValue" placeholder="Max" title="Max" style="margin-top : 5px;">';
    tr += '</td>';
    tr += '<td>';
    if (is_numeric(_cmd.id)) {
        tr += '<a class="btn btn-default btn-xs cmdAction expertModeVisible" data-action="configure"><i class="fa fa-cogs"></i></a> ';
        tr += '<a class="btn btn-default btn-xs cmdAction" data-action="test"><i class="fa fa-rss"></i> Tester</a>';
    }
    tr += '<i class="fa fa-minus-circle pull-right cmdAction cursor" data-action="remove"></i></td>';
    tr += '</tr>';
    $('#table_cmd tbody').append(tr);
    var tr = $('#table_cmd tbody tr:last');
    jeedom.eqLogic.builSelectCmd({
        id: $(".li_eqLogic.active").attr('data-eqLogic_id'),
        filter: {type: 'info'},
        error: function (error) {
            $('#div_alert').showAlert({message: error.message, level: 'danger'});
        },
        success: function (result) {
            tr.find('.cmdAttr[data-l1key=value]').append(result);
            tr.find('.cmdAttr[data-l1key=configuration][data-l2key=updateCmdId]').append(result);
            tr.setValues(_cmd, '.cmdAttr');
            jeedom.cmd.changeType(tr, init(_cmd.subType));
        }
    });
}

/* Fonction appelé pour mettre l'affichage à jour pour la sauvegarde en temps réel
 * _data: les détails des informations à sauvegardé
 */
function displayEqLogic(_data) {
    
}

/* Fonction appelé pour mettre l'affichage à jour de la sidebar et du container 
 * en asynchrone, est appelé en début d'affichage de page, au moment de la sauvegarde,
 * de la suppression, de la création
 * _callback: obligatoire, permet d'appeler une fonction en fin de traitement
 */
function updateDisplayPlugin(_callback) {
    $.ajax({
        type: "POST",
        url: "plugins/tvpanasonic/core/ajax/tvpanasonic.ajax.php",
        data: {
            action: "getAll"
        },
        dataType: 'json',
        error: function (request, status, error) {
            handleAjaxError(request, status, error);
        },
        success: function (data) {
            //console.log(data);
            if (data.state !== 'ok') {
                $('#div_alert').showAlert({message: data.result, level: 'danger'});
                return;
            }
            var htmlSideBar = '';
            var htmlContainer = '';
            // Le plus Geant - ne pas supprimer
            htmlContainer += '<div class="cursor eqLogicAction" data-action="add" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
            htmlContainer += '<center>';
            htmlContainer += '<i class="fa fa-plus-circle" style="font-size : 7em;color:#94ca02;"></i>';
            htmlContainer += '</center>';
            htmlContainer += '<span style="font-size : 1.1em;position:relative; top : 23px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;color:#94ca02"><center>Ajouter</center></span>';
            htmlContainer += '</div>';
            // la liste des équipements
            var eqLogics = data.result;
            for (var i  in eqLogics) {
                htmlSideBar += '<li class="cursor li_eqLogic" data-eqLogic_id="' + eqLogics[i].id + '"><a>' + eqLogics[i].humanSidebar + '</a></li>';
                // Définition du format des icones de la page principale - ne pas modifier
                htmlContainer += '<div class="eqLogicDisplayCard cursor" data-eqLogic_id="' + eqLogics[i].id + '" style="background-color : #ffffff; height : 200px;margin-bottom : 10px;padding : 5px;border-radius: 2px;width : 160px;margin-left : 10px;" >';
                htmlContainer += "<center>";
                // lien vers l'image de votre icone
                htmlContainer += '<img src="plugins/tvpanasonic/doc/images/tvpanasonic_icon.png" height="105" width="95" />';
                htmlContainer += "</center>";
                // Nom de votre équipement au format human
                htmlContainer += '<span style="font-size : 1.1em;position:relative; top : 15px;word-break: break-all;white-space: pre-wrap;word-wrap: break-word;"><center>' + eqLogics[i].humanContainer + '</center></span>';
                htmlContainer += '</div>';
            }
            $('#ul_eqLogicView').empty();
            $('#ul_eqLogicView').append(htmlSideBar);
            $('.eqLogicThumbnailContainer').remove();
            $('.eqLogicThumbnailDisplay legend').after($('<div class="eqLogicThumbnailContainer">').html(htmlContainer));
            $('.eqLogicThumbnailContainer').packery();
            $("img.lazy").lazyload({
                container: $(".eqLogicThumbnailContainer"),
                event : "sporty",
                skip_invisible : false
            });
            $("img.lazy").trigger("sporty");
            $("img.lazy").each(function () {
                var el = $(this);
                if (el.attr('data-original2') !== undefined) {
                    $("<img>", {
                        src: el.attr('data-original'),
                        error: function () {
                            $("<img>", {
                                src: el.attr('data-original2'),
                                error: function () {
                                    if (el.attr('data-original3') !== undefined) {
                                        $("<img>", {
                                            src: el.attr('data-original3'),
                                            error: function () {
                                                el.lazyload({
                                                    event: "sporty"
                                                });
                                                el.trigger("sporty");
                                            },
                                            load: function () {
                                                el.attr("data-original", el.attr('data-original3'));
                                                el.lazyload({
                                                    event: "sporty"
                                                });
                                                el.trigger("sporty");
                                            }
                                        });
                                    } else {
                                        el.lazyload({
                                            event: "sporty"
                                        });
                                        el.trigger("sporty");
                                    }
                                },
                                load: function () {
                                    el.attr("data-original", el.attr('data-original2'));
                                    el.lazyload({
                                        event: "sporty"
                                    });
                                    el.trigger("sporty");
                                }
                            });
                        },
                        load: function () {
                            el.lazyload({
                                event: "sporty"
                            });
                            el.trigger("sporty");
                        }
                    });
                } else {
                    el.lazyload({
                        event: "sporty"
                    });
                    el.trigger("sporty");
                }
            });
            if(_callback !== undefined)
                _callback();
            modifyWithoutSave = false;
        }
    });
}
