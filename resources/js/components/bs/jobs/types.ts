export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    total: number;
    prev_page_url: string | null;
    next_page_url: string | null;
};

export type JobFilters = {
    q: string | null;
    country: string | null;
    occupation: string | null;
    no_worker_costs: boolean;
    accommodation: boolean;
    salary_disclosed: boolean;
    sort: 'newest' | 'deadline';
};
