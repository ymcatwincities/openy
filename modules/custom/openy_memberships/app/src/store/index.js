import mutations from '../mutations'

export default {
    state: {
      step: 0,
      steps: ['BranchSelectorHome', 'Family', 'DiscountFinder', 'Summary'],
      location: null,
      family: {
        adults: 0,
        youth: 0,
        seniors: 0
      }
    },
    mutations
}