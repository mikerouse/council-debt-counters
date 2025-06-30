import './bootstrap';
import { createApp, ref, onMounted, computed } from 'vue';

const app = createApp({
    setup() {
        const totalDebt = ref(0);
        const displayedDebt = ref(0);
        const searchQuery = ref('');
        const councils = ref([]);

        const fetchTotals = async () => {
            try {
                const response = await axios.get('/api/totals');
                totalDebt.value = response.data.totalDebt || 0;
                animateDebt();
            } catch (e) {
                console.error(e);
            }
        };

        const animateDebt = () => {
            const step = Math.ceil(totalDebt.value / 100);
            const interval = setInterval(() => {
                if (displayedDebt.value >= totalDebt.value) {
                    displayedDebt.value = totalDebt.value;
                    clearInterval(interval);
                } else {
                    displayedDebt.value += step;
                }
            }, 20);
        };

        const searchCouncils = async () => {
            if (!searchQuery.value) {
                councils.value = [];
                return;
            }
            try {
                const response = await axios.get('/api/councils/search', {
                    params: { q: searchQuery.value },
                });
                councils.value = response.data;
            } catch (e) {
                console.error(e);
            }
        };

        onMounted(fetchTotals);

        const formattedDebt = computed(() => {
            return new Intl.NumberFormat('en-GB', {
                style: 'currency',
                currency: 'GBP',
                maximumFractionDigits: 0,
            }).format(displayedDebt.value);
        });

        return {
            totalDebt,
            displayedDebt,
            searchQuery,
            councils,
            searchCouncils,
            formattedDebt,
        };
    },
});

app.mount('#app');
