<template>
    <div id="raw-material-low-stock" class="px-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="ns-button">
                <button @click="loadAlerts()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Actualiser' ) }}</span>
                </button>
            </div>
            <div class="ns-button">
                <button @click="printAlerts()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-print text-xl"></i>
                    <span class="pl-2">{{ __( 'Imprimer' ) }}</span>
                </button>
            </div>
        </div>

        <!-- Alert Banner -->
        <div
            v-if="!isLoading && alerts.length > 0"
            class="flex items-center gap-3 p-4 mb-4 rounded shadow ns-box border-l-4 border-error-primary anim-duration-500 fade-in-entrance"
        >
            <i class="las la-exclamation-triangle text-3xl text-error-primary"></i>
            <div>
                <p class="font-semibold text-error-primary">
                    {{ __( '{count} matière(s) en dessous du seuil d\'alerte' ).replace( '{count}', alerts.length ) }}
                </p>
                <p class="text-sm">{{ __( 'Ces matières nécessitent un réapprovisionnement urgent.' ) }}</p>
            </div>
        </div>

        <div
            v-if="!isLoading && alerts.length === 0"
            class="flex items-center gap-3 p-4 mb-4 rounded shadow ns-box border-l-4 border-success-primary anim-duration-500 fade-in-entrance"
        >
            <i class="las la-check-circle text-3xl text-success-primary"></i>
            <div>
                <p class="font-semibold text-success-primary">{{ __( 'Tous les stocks sont dans les niveaux acceptables.' ) }}</p>
            </div>
        </div>

        <!-- Alerts Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance" id="alerts-print-area">
            <div class="ns-box-body">
                <div class="flex justify-between items-center p-3 border-b">
                    <div class="text-sm text-fontcolor">
                        <ul>
                            <li>{{ __( 'Date : {date}' ).replace( '{date}', ns.date.current ) }}</li>
                            <li>{{ __( 'Document : Alertes stock matières premières' ) }}</li>
                            <li>{{ __( 'Par : {user}' ).replace( '{user}', ns.user.username ) }}</li>
                        </ul>
                    </div>
                </div>
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Matière première' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Unité' ) }}</th>
                            <th width="130" class="border border-error-secondary bg-error-primary p-2 text-right">{{ __( 'Stock actuel' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Seuil alerte' ) }}</th>
                            <th width="150" class="border p-2 text-right">{{ __( 'Manque' ) }}</th>
                            <th width="130" class="border p-2 text-right">{{ __( 'Coût réappro. estimé' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="6" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="alerts.length === 0">
                            <td colspan="6" class="p-4 border text-center">
                                {{ __( 'Aucune alerte de stock.' ) }}
                            </td>
                        </tr>
                        <tr v-for="item in alerts" :key="item.id" class="text-sm border-error-secondary bg-error-primary">
                            <td class="p-2 border font-semibold">{{ item.name }}</td>
                            <td class="p-2 border">{{ item.unit }}</td>
                            <td class="p-2 border border-error-secondary bg-error-primary text-right font-bold">
                                {{ item.stock_quantity }}
                            </td>
                            <td class="p-2 border text-right">{{ item.alert_quantity }}</td>
                            <td class="p-2 border text-right">
                                {{ Math.max( 0, item.alert_quantity - item.stock_quantity ) }}
                            </td>
                            <td class="p-2 border text-right">
                                {{ nsCurrency( Math.max( 0, item.alert_quantity - item.stock_quantity ) * item.cost_per_unit ) }}
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="alerts.length > 0">
                        <tr class="font-semibold">
                            <td colspan="5" class="p-2 border text-right">{{ __( 'Coût total estimé de réapprovisionnement' ) }}</td>
                            <td class="p-2 border text-right">{{ nsCurrency( totalRestockCost ) }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import { nsCurrency } from '~/filters/currency';
import nsSpinner from '~/components/ns-spinner.vue';

export default {
    name: 'ns-raw-material-low-stock',
    components: { nsSpinner },
    data() {
        return {
            ns: window.ns,
            alerts: [],
            isLoading: false,
        };
    },
    computed: {
        totalRestockCost() {
            return this.alerts.reduce( ( sum, i ) => {
                const deficit = Math.max( 0, i.alert_quantity - i.stock_quantity );
                return sum + deficit * i.cost_per_unit;
            }, 0 );
        },
    },
    mounted() {
        this.loadAlerts();
    },
    methods: {
        __,
        nsCurrency,
        loadAlerts() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/stock-report/low-stock' ).subscribe({
                next: ( data ) => { this.alerts = data; this.isLoading = false; },
                error: () => { nsSnackBar.error( __( 'Impossible de charger les alertes.' ) ); this.isLoading = false; },
            });
        },
        printAlerts() {
            this.$htmlToPaper( 'alerts-print-area' );
        },
    },
};
</script>
