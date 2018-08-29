import {
    FETCH_ALERTS_BEGIN,
    FETCH_ALERTS_SUCCESS,
    FETCH_ALERTS_FAILURE
} from '../actions/backend';


const initState = {
    loading: false,
    error: null,
    alerts: {}
};

export default function activityTypes(state = initState, action) {
    switch (action.type) {
        case FETCH_ALERTS_BEGIN:
            return {
                ...state,
                loading: true,
                error: null
            };

        case FETCH_ALERTS_SUCCESS:

            const alerts = action.payload;

            return {
                ...state,
                loading: false,
                alerts: alerts,
            };

        case FETCH_ALERTS_FAILURE:
            return {
                ...state,
                loading: false,
                error: action.payload
            };


        default:
            return state;
    }
}