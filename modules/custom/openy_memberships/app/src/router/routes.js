import BranchSelector from '../views/BranchSelector.vue'
import Summary from '../views/Summary.vue'
import DiscountFinder from '../views/DiscountFinder.vue'
import Family from '../views/Family.vue'

export default [
  {
    path: '/memberships/branch-selector',
    name: 'BranchSelectorHome',
    component: BranchSelector
  },
  {
    path: '/memberships/discount-finder',
    name: 'DiscountFinder',
    component: DiscountFinder
  },
  {
    path: '/memberships/family',
    name: 'Family',
    component: Family
  },
  {
    path: '/memberships/summary',
    name: 'Summary',
    component: Summary
  }
]