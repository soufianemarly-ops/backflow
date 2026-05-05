<template>
    <div id="recipe-section" class="px-4">
        <!-- Toolbar -->
        <div class="flex flex-wrap gap-2 mb-4">
            <div class="ns-button">
                <button @click="openCreateForm()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-plus text-xl"></i>
                    <span class="pl-2">{{ __( 'Nouvelle recette' ) }}</span>
                </button>
            </div>
            <div class="ns-button">
                <button @click="loadRecipes()" class="rounded flex items-center shadow py-1 px-3">
                    <i class="las la-sync-alt text-xl"></i>
                    <span class="pl-2">{{ __( 'Actualiser' ) }}</span>
                </button>
            </div>
        </div>

        <!-- Recipes Table -->
        <div class="ns-box shadow rounded anim-duration-500 fade-in-entrance">
            <div class="ns-box-body">
                <table class="table ns-table w-full">
                    <thead>
                        <tr>
                            <th class="border p-2 text-left">{{ __( 'Nom de la recette' ) }}</th>
                            <th class="border p-2 text-left">{{ __( 'Produit lié' ) }}</th>
                            <th width="120" class="border p-2 text-center">{{ __( 'Ingrédients' ) }}</th>
                            <th width="120" class="border p-2 text-center">{{ __( 'Actions' ) }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="isLoading">
                            <td colspan="4" class="p-4 border text-center">
                                <ns-spinner></ns-spinner>
                            </td>
                        </tr>
                        <tr v-else-if="recipes.length === 0">
                            <td colspan="4" class="p-4 border text-center">
                                {{ __( 'Aucune recette trouvée.' ) }}
                            </td>
                        </tr>
                        <tr v-for="recipe in recipes" :key="recipe.id" class="text-sm">
                            <td class="p-2 border font-semibold">{{ recipe.name }}</td>
                            <td class="p-2 border">{{ recipe.product ? recipe.product.name : '—' }}</td>
                            <td class="p-2 border text-center">{{ recipe.ingredients_count }}</td>
                            <td class="p-2 border text-center">
                                <div class="flex justify-center gap-1">
                                    <button @click="openEditForm( recipe )" class="ns-button rounded px-2 py-1 text-xs">
                                        <i class="las la-edit"></i>
                                    </button>
                                    <button @click="deleteRecipe( recipe )" class="ns-button error rounded px-2 py-1 text-xs">
                                        <i class="las la-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Create / Edit Modal -->
        <div v-if="showForm" class="fixed inset-0 z-50 flex items-center justify-center" style="background: rgba(0,0,0,0.5);">
            <div class="ns-box shadow-lg rounded w-full max-w-2xl mx-4" style="max-height:90vh;overflow-y:auto;">
                <div class="ns-box-header flex justify-between items-center p-4 border-b">
                    <h3 class="font-semibold text-lg">{{ editingRecipe ? __( 'Modifier la recette' ) : __( 'Nouvelle recette' ) }}</h3>
                    <button @click="closeForm()" class="ns-button rounded px-2 py-1">
                        <i class="las la-times text-xl"></i>
                    </button>
                </div>
                <div class="ns-box-body p-4 flex flex-col gap-3">
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Nom de la recette' ) }} *</label>
                            <input v-model="form.name" type="text" class="ns-input w-full rounded border px-3 py-2 text-sm" :placeholder="__( 'ex: Pain au chocolat' )">
                        </div>

                        <!-- Product autocomplete -->
                        <div class="flex-1 relative">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Produit lié (optionnel)' ) }}</label>

                            <!-- Selected product display -->
                            <div v-if="form.product_id" class="flex items-center ns-input w-full rounded border px-3 py-2 text-sm">
                                <span class="flex-1 truncate">{{ form.product_name }}</span>
                                <button @click="clearProduct()" type="button" class="ml-2 opacity-60 hover:opacity-100">
                                    <i class="las la-times"></i>
                                </button>
                            </div>

                            <!-- Search input + dropdown -->
                            <div v-else class="relative">
                                <div class="flex items-center ns-input w-full rounded border px-3 py-2 text-sm">
                                    <input
                                        v-model="productSearch"
                                        type="text"
                                        class="flex-1 outline-none bg-transparent"
                                        :placeholder="__( 'Rechercher un produit...' )"
                                        @blur="hideProductResults()"
                                    >
                                    <ns-spinner v-if="isSearchingProduct" size="4"></ns-spinner>
                                </div>
                                <div
                                    v-if="showProductResults && productResults.length > 0"
                                    class="absolute z-50 w-full ns-box shadow-lg rounded border mt-1"
                                >
                                    <ul class="ns-vertical-menu" style="max-height:200px;overflow-y:auto;">
                                        <li
                                            v-for="product in productResults"
                                            :key="product.id"
                                            @mousedown.prevent="selectProduct( product )"
                                            class="border-b p-2 cursor-pointer text-sm"
                                        >
                                            <span class="font-semibold block">{{ product.name }}</span>
                                            <span v-if="product.sku" class="text-xs opacity-60">{{ product.sku }}</span>
                                        </li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Rendement (quantité)' ) }}</label>
                            <input v-model.number="form.yield_quantity" type="number" min="0" step="0.01" class="ns-input w-full rounded border px-3 py-2 text-sm">
                        </div>
                        <div class="flex-1">
                            <label class="block text-sm font-medium mb-1">{{ __( 'Unité de rendement' ) }}</label>
                            <select v-model="form.yield_unit" class="ns-select w-full rounded border px-3 py-2 text-sm">
                                <option value="pcs">pcs</option>
                                <option value="kg">kg</option>
                                <option value="g">g</option>
                                <option value="L">L</option>
                            </select>
                        </div>
                    </div>

                    <!-- Ingrédients -->
                    <div>
                        <div class="flex justify-between items-center mb-2">
                            <label class="block text-sm font-medium">{{ __( 'Ingrédients' ) }}</label>
                            <button @click="addIngredient()" :disabled="isLoadingIngredients" class="ns-button rounded px-2 py-1 text-xs">
                                <i class="las la-plus"></i> {{ __( 'Ajouter' ) }}
                            </button>
                        </div>
                        <div v-if="isLoadingIngredients" class="flex justify-center py-3">
                            <ns-spinner size="6"></ns-spinner>
                        </div>
                        <template v-else>
                            <div v-for="(ing, idx) in form.ingredients" :key="idx" class="flex gap-2 mb-2 items-center">
                                <select v-model="ing.raw_material_id" class="ns-select flex-1 rounded border px-2 py-1 text-sm">
                                    <option value="">{{ __( '-- Matière --' ) }}</option>
                                    <option v-for="m in rawMaterials" :key="m.id" :value="m.id">{{ m.name }}</option>
                                </select>
                                <input v-model.number="ing.quantity" type="number" min="0" step="0.01" class="ns-input w-24 rounded border px-2 py-1 text-sm" :placeholder="__( 'Qté' )">
                                <select v-model="ing.unit" class="ns-select w-20 rounded border px-2 py-1 text-sm">
                                    <option value="g">g</option>
                                    <option value="kg">kg</option>
                                    <option value="L">L</option>
                                    <option value="ml">ml</option>
                                    <option value="pcs">pcs</option>
                                </select>
                                <button @click="removeIngredient( idx )" class="ns-button error rounded px-2 py-1 text-xs">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                            <p v-if="form.ingredients.length === 0" class="text-sm text-gray-400">{{ __( 'Aucun ingrédient ajouté.' ) }}</p>
                        </template>
                    </div>
                </div>
                <div class="ns-box-footer flex justify-end gap-2 p-4 border-t">
                    <button @click="closeForm()" class="ns-button rounded px-4 py-2">{{ __( 'Annuler' ) }}</button>
                    <button @click="saveRecipe()" :disabled="isSaving" class="ns-button info rounded px-4 py-2">
                        <ns-spinner v-if="isSaving" size="4"></ns-spinner>
                        <span v-else>{{ __( 'Enregistrer' ) }}</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
import { nsHttpClient, nsSnackBar } from '~/bootstrap';
import { __ } from '~/libraries/lang';
import nsSpinner from '~/components/ns-spinner.vue';

export default {
    name: 'ns-recipe-list',
    components: { nsSpinner },
    data() {
        return {
            recipes: [],
            rawMaterials: [],
            isLoading: false,
            isSaving: false,
            isLoadingIngredients: false,
            showForm: false,
            editingRecipe: null,
            form: this.emptyForm(),

            productSearch: '',
            productResults: [],
            productSearchTimer: null,
            showProductResults: false,
            isSearchingProduct: false,
        };
    },
    watch: {
        productSearch( val ) {
            clearTimeout( this.productSearchTimer );
            this.showProductResults = false;

            if ( val.length < 2 ) {
                this.productResults = [];
                return;
            }

            this.isSearchingProduct = true;
            this.productSearchTimer = setTimeout( () => this.searchProducts( val ), 500 );
        },
    },
    mounted() {
        this.loadRecipes();
        this.loadRawMaterials();
    },
    methods: {
        __,
        emptyForm() {
            return { name: '', product_id: null, product_name: '', yield_quantity: 1, yield_unit: 'pcs', ingredients: [] };
        },
        loadRecipes() {
            this.isLoading = true;
            nsHttpClient.get( '/api/ns-raw-material/recipes' ).subscribe({
                next: ( data ) => { this.recipes = data; this.isLoading = false; },
                error: () => { nsSnackBar.error( __( 'Impossible de charger les recettes.' ) ); this.isLoading = false; },
            });
        },
        loadRawMaterials() {
            nsHttpClient.get( '/api/ns-raw-material/raw-materials' ).subscribe({
                next: ( data ) => { this.rawMaterials = data; },
                error: () => {},
            });
        },
        openCreateForm() {
            this.editingRecipe = null;
            this.form = this.emptyForm();
            this.resetProductSearch();
            this.showForm = true;
        },
        openEditForm( recipe ) {
            this.editingRecipe = recipe;
            this.form = {
                name:           recipe.name,
                product_id:     recipe.product_id || null,
                product_name:   recipe.product ? recipe.product.name : '',
                yield_quantity: recipe.yield_quantity || 1,
                yield_unit:     recipe.yield_unit || 'pcs',
                ingredients:    [],
            };
            this.resetProductSearch();
            this.isLoadingIngredients = true;
            this.showForm = true;

            console.log( '[openEditForm] fetching recipe id:', recipe.id, 'url:', `/api/ns-raw-material/recipes/${recipe.id}` );
            nsHttpClient.get( `/api/ns-raw-material/recipes/${recipe.id}` ).subscribe({
                next: ( data ) => {
                    console.log( '[openEditForm] API response (typeof):', typeof data, data );
                    console.log( '[openEditForm] data.ingredients:', data?.ingredients );
                    console.log( '[openEditForm] data.id:', data?.id );
                    this.form.ingredients = ( data.ingredients || [] ).map( i => ({
                        raw_material_id: i.raw_material_id,
                        quantity:        i.quantity,
                        unit:            i.raw_material?.unit || 'kg',
                    }) );
                    this.isLoadingIngredients = false;
                },
                error: () => {
                    nsSnackBar.error( __( 'Impossible de charger les ingrédients.' ) );
                    this.isLoadingIngredients = false;
                },
            });
        },
        closeForm() { this.showForm = false; },
        addIngredient() {
            this.form.ingredients.push({ raw_material_id: '', quantity: 0, unit: 'kg' });
        },
        removeIngredient( idx ) {
            this.form.ingredients.splice( idx, 1 );
        },

        // --- Product autocomplete ---
        searchProducts( query ) {
            nsHttpClient.post( '/api/products/search', { search: query } )
                .subscribe({
                    next: ( results ) => {
                        this.productResults     = results;
                        this.showProductResults = results.length > 0;
                        this.isSearchingProduct = false;
                    },
                    error: () => {
                        this.isSearchingProduct = false;
                    },
                });
        },
        selectProduct( product ) {
            this.form.product_id   = product.id;
            this.form.product_name = product.name;
            this.resetProductSearch();
        },
        clearProduct() {
            this.form.product_id   = null;
            this.form.product_name = '';
        },
        hideProductResults() {
            // Delayed so mousedown.prevent on list items fires before blur clears results
            setTimeout( () => { this.showProductResults = false; }, 150 );
        },
        resetProductSearch() {
            this.productSearch      = '';
            this.productResults     = [];
            this.showProductResults = false;
            this.isSearchingProduct = false;
            clearTimeout( this.productSearchTimer );
        },

        saveRecipe() {
            if ( ! this.form.name ) { nsSnackBar.error( __( 'Le nom est obligatoire.' ) ); return; }
            this.isSaving = true;
            const request = this.editingRecipe
                ? nsHttpClient.put( `/api/ns-raw-material/recipes/${this.editingRecipe.id}`, this.form )
                : nsHttpClient.post( '/api/ns-raw-material/recipes', this.form );

            request.subscribe({
                next: () => {
                    nsSnackBar.success( __( 'Recette enregistrée.' ) );
                    this.isSaving = false;
                    this.closeForm();
                    this.loadRecipes();
                },
                error: ( error ) => {
                    nsSnackBar.error( error.message || __( 'Une erreur est survenue.' ) );
                    this.isSaving = false;
                },
            });
        },
        deleteRecipe( recipe ) {
            if ( ! confirm( __( 'Supprimer cette recette ?' ) ) ) return;
            nsHttpClient.delete( `/api/ns-raw-material/recipes/${recipe.id}` ).subscribe({
                next: () => { nsSnackBar.success( __( 'Recette supprimée.' ) ); this.loadRecipes(); },
                error: () => { nsSnackBar.error( __( 'Impossible de supprimer la recette.' ) ); },
            });
        },
    },
};
</script>
