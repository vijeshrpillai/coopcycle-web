import { createSelector } from 'reselect'
import { moment } from '../../coopcycle-frontend-js'
import { selectTaskLists as selectTaskListsBase, selectUnassignedTasks, selectAllTasks } from '../../coopcycle-frontend-js/dispatch/redux'
import { filter, orderBy, forEach, find, reduce } from 'lodash'
import { isTaskVisible } from './utils'

export const selectTaskLists = createSelector(
  selectTaskListsBase,
  taskLists => orderBy(taskLists, 'username')
)

export const selectBookedUsernames = createSelector(
  selectTaskLists,
  taskLists => taskLists.map(taskList => taskList.username)
)

export const selectGroups = createSelector(
  selectUnassignedTasks,
  state => state.taskListGroupMode,
  (unassignedTasks, taskListGroupMode) => {

    if (taskListGroupMode !== 'GROUP_MODE_FOLDERS') {
      return []
    }

    const groupsMap = new Map()
    const groups = []

    const tasksWithGroup = filter(unassignedTasks, task => Object.prototype.hasOwnProperty.call(task, 'group') && task.group)

    forEach(tasksWithGroup, task => {
      const keys = Array.from(groupsMap.keys())
      const group = find(keys, group => group.id === task.group.id)
      if (!group) {
        groupsMap.set(task.group, [ task ])
      } else {
        groupsMap.get(group).push(task)
      }
    })

    groupsMap.forEach((tasks, group) => {
      groups.push({
        ...group,
        tasks
      })
    })

    return groups
  }
)

export const selectStandaloneTasks = createSelector(
  selectUnassignedTasks,
  state => state.taskListGroupMode,
  (unassignedTasks, taskListGroupMode) => {

    let standaloneTasks = unassignedTasks

    if (taskListGroupMode === 'GROUP_MODE_FOLDERS') {
      standaloneTasks = filter(unassignedTasks, task => !Object.prototype.hasOwnProperty.call(task, 'group') || !task.group)
    }

    // Order by dropoff desc, with pickup before
    if (taskListGroupMode === 'GROUP_MODE_DROPOFF_DESC') {

      const dropoffTasks = filter(standaloneTasks, t => t.type === 'DROPOFF')

      dropoffTasks.sort((a, b) => {
        return moment(a.doneBefore).isBefore(b.doneBefore) ? -1 : 1
      })

      const grouped = reduce(dropoffTasks, (acc, task) => {
        if (task.previous) {
          const prev = find(standaloneTasks, t => t['@id'] === task.previous)
          if (prev) {
            acc.push(prev)
          }
        }
        acc.push(task)

        return acc
      }, [])

      standaloneTasks = grouped
    } else {
      standaloneTasks.sort((a, b) => {
        return moment(a.doneBefore).isBefore(b.doneBefore) ? -1 : 1
      })
    }

    return standaloneTasks
  }
)

export const selectVisibleTaskIds = createSelector(
  selectAllTasks,
  state => state.filters,
  state => state.date,
  (tasks, filters, date) => filter(tasks, task => isTaskVisible(task, filters, date)).map(task => task['@id'])
)
