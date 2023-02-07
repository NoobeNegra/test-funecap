import {stat} from "@babel/core/lib/gensync-utils/fs";

const axios = require('axios');
const $ = require('jquery');

export function requestMatches(id){

    axios.get('/matches/'+id+'/2022')
        .then(function (response) {
            // handle success
            let appended ="<ul>";
            response['data']['data'].forEach(obj => {
                Object.entries(obj).forEach(([key, value]) => {
                    appended+= "<li>"+value['home']['team']+" ("+value['home']['goals']+") vs "+value['away']['team']+" ("+value['away']['goals']+")";
                    appended+='<a href="#" class="btn btn-primary request-matches" data-match="'+value.id+'">Voir stats</a>';
                    appended+="</li>";
                });
            });
            appended+="</ul>";
            $('#sidePanel').html(appended);
        })
        .catch(function (error) {
            // handle error
            console.log(error);
        })
        .then(function () {
            // always executed
        });
}

export function requestMatchStats(id){

    axios.get('/match/'+id)
        .then(function (response) {
            // handle success
            let appended = "";
            response['data']['data'].forEach(obj => {
                Object.entries(obj).forEach(([key, value]) => {
                    appended+= value['team']['name'];
                    appended +="<ul>";
                    Object.entries(value['statistics']).forEach(([keyst, stats]) => {
                        appended+= "<li>"+stats['type']+' '+stats['value']+"</li>"
                    })
                    appended+="</ul>";
                });
            });
            $('#sidePanel').html(appended);
        })
        .catch(function (error) {
            // handle error
            console.log(error);
        })
        .then(function () {
            // always executed
        });
}

$(document).ready(function() {
    $('.request-matches').click(function()
    {
        requestMatches($(this).data('leagueid'));
    })
});