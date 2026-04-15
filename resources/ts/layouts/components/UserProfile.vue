<script setup lang="ts">
import avatar1 from '@images/avatars/avatar-1.png';

import { useRouter } from 'vue-router';
const router = useRouter()
const StoredUserName = localStorage.getItem('user_name')

const handleLogout = () => {
  localStorage.removeItem('auth_token')
  localStorage.removeItem('company_id')
  localStorage.removeItem('tipo_de_usuario')
  localStorage.removeItem('user_name')
  localStorage.clear() 
  router.push({ name: 'login' })
}

</script>


<template>
  <VBadge
    dot
    location="bottom right"
    offset-x="3"
    offset-y="3"
    bordered
    color="success"
  >
    <VAvatar
      class="cursor-pointer"
      color="primary"
      variant="tonal"
    >
      <VImg :src="avatar1" />

      <!-- SECTION Menu -->
      <VMenu
        activator="parent"
        width="230"
        location="bottom end"
        offset="14px"
      >
            
            <span
            class="text-body-2 font-weight-medium text-high-emphasis"
            style="white-space: nowrap;"
          >
          
          </span>
      

        <VList>
          <!-- 👉 User Avatar & Name -->
          <VListItem>
            <template #prepend>
              <VListItemAction start>
                <VBadge
                  dot
                  location="bottom right"
                  offset-x="3"
                  offset-y="3"
                  color="success"
                >
                  <VAvatar
                    color="primary"
                    variant="tonal"
                  >
                    <VImg :src="avatar1" />
                  </VAvatar>
                </VBadge>
              </VListItemAction>
            </template>

            
            <VListItemSubtitle>Admin</VListItemSubtitle>
            
          </VListItem>

          <VListItemTitle class="font-weight-semibold ml-3 " style="font-size: 9px;color:blue">
              {{StoredUserName}}
          </VListItemTitle>

          <VDivider class="my-2" />

          <!-- 👉 Profile -->
          <VListItem link>
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-user"
                size="22"
              />
            </template>

            <VListItemTitle>Profile</VListItemTitle>
          </VListItem>

          <!-- 👉 Settings -->
          <VListItem link>
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-settings"
                size="22"
              />
            </template>

            <VListItemTitle>Settings</VListItemTitle>
          </VListItem>

          <!-- 👉 Pricing -->
          <VListItem link>
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-currency-dollar"
                size="22"
              />
            </template>

            <VListItemTitle>Pricing1</VListItemTitle>
          </VListItem>

          <!-- 👉 FAQ -->
          <VListItem link>
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-help"
                size="22"
              />
            </template>

            <VListItemTitle>FAQ</VListItemTitle>
          </VListItem>

          <!-- Divider -->
          <VDivider class="my-2" />

          <!-- 👉 Logout -->
          <!-- <VListItem to="/login">
            <template #prepend>
              <VIcon
                class="me-2"
                icon="tabler-logout"
                size="22"
              />
            </template> -->

            <VListItem @click="handleLogout" style="cursor: pointer;">
               Logout
            </VListItem>

            <!-- <VListItemTitle>Logout</VListItemTitle> -->
          <!-- </VListItem> -->
        </VList>
      </VMenu>
      <!-- !SECTION -->
    </VAvatar>
  </VBadge>
  
  
</template>
