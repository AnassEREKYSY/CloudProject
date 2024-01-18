terraform {
    required_providers {
        azurerm = {
            source  = "hashicorp/azurerm"
            version = "3.87.0"
        }
    }   
    required_version = ">= 0.14.9"
}

provider "azurerm" {
    features {
        resource_group {
          prevent_deletion_if_contains_resources = false
        }
    }
}

resource "azurerm_resource_group" "rg" {
        name     = "omar-anass-terraform-rg"
        location = "westeurope"
}

resource "azurerm_public_ip" "omar-terraform-public-ip" {
    name                = "omar-anass-terraform-public-ip"
    location            = azurerm_resource_group.rg.location
    resource_group_name = azurerm_resource_group.rg.name
    allocation_method   = "Static"
    sku                 = "Standard"
}


resource "azurerm_network_security_group" "nsg" {
  name                = "omar-anass-terraform-nsg"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name
  
}



resource "azurerm_network_security_rule" "ssh_rule" {
  name                        = "AllowSSH"
  priority                    = 1001
  direction                   = "Inbound"
  access                      = "Allow"
  protocol                    = "Tcp"
  source_port_range           = "*"
  destination_port_range      = "22"
  source_address_prefix       = "*"
  destination_address_prefix  = "*"
  resource_group_name         = azurerm_resource_group.rg.name
  network_security_group_name = azurerm_network_security_group.nsg.name
}

resource "azurerm_virtual_network" "vnet" {
        name                = "omar-anass-terraform-vnet"
        resource_group_name = azurerm_resource_group.rg.name
        address_space       = ["10.0.0.0/16"]
        location            = azurerm_resource_group.rg.location
}

resource "azurerm_subnet" "subnet" {
        name                 = "omar-anass-terraform-subnet"
        resource_group_name  = azurerm_resource_group.rg.name
        virtual_network_name = azurerm_virtual_network.vnet.name
        address_prefixes      = ["10.0.0.0/24"]
}



resource "azurerm_network_interface" "nic" {
  name                = "omar-anass-terraform-nic"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name


  ip_configuration {
    name                          = "omar-anass-terraform-nic-ipconfig"
    subnet_id                     = azurerm_subnet.subnet.id
    private_ip_address_allocation = "Dynamic"
    public_ip_address_id          = azurerm_public_ip.omar-terraform-public-ip.id
  }


}

resource "azurerm_network_interface_security_group_association" "nsg_association" {
  network_interface_id      = azurerm_network_interface.nic.id
  network_security_group_id = azurerm_network_security_group.nsg.id

}


resource "azurerm_linux_virtual_machine" "vm" {
  name                = "reservation-vm-omar-anass"
  resource_group_name = azurerm_resource_group.rg.name
  location            = azurerm_resource_group.rg.location
  size                = "Standard_F2"
  admin_username      = var.vm_admin_username
  admin_password      = var.vm_admin_password
  disable_password_authentication = false
  network_interface_ids = [
    azurerm_network_interface.nic.id,
  ]

  os_disk {
    caching              = "ReadWrite"
    storage_account_type = "Standard_LRS"
  }

  source_image_reference {
    publisher = "Canonical"
    offer     = "0001-com-ubuntu-server-jammy"
    sku       = "22_04-lts"
    version   = "latest"
  }
}

resource "azurerm_mysql_server" "omar-anass-terraform-mysql" {
  name                = "omar-anass-terraform-mysql"
  location            = azurerm_resource_group.rg.location
  resource_group_name = azurerm_resource_group.rg.name

  administrator_login          = var.db_admin_username
  administrator_login_password = var.db_admin_password

  sku_name   = "B_Gen5_2"
  storage_mb = 5120
  version    = "5.7"

  auto_grow_enabled                 = true
  backup_retention_days             = 7
  geo_redundant_backup_enabled      = false
  infrastructure_encryption_enabled = false
  public_network_access_enabled     = true
  ssl_enforcement_enabled           = true
  ssl_minimal_tls_version_enforced  = "TLS1_2"
}

resource "azurerm_storage_account" "omar-anass-terraform-storage" {
  name                     = "omaranassstorage"
  resource_group_name      = azurerm_resource_group.rg.name
  location                 = azurerm_resource_group.rg.location
  account_tier             = "Standard"
  account_replication_type = "LRS"
}

resource "azurerm_storage_container" "omaranasscontainer" {
  name                  = "omaranasscontainer"
  storage_account_name  = azurerm_storage_account.omar-anass-terraform-storage.name
  container_access_type = "private"
}


