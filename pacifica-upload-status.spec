Name:		pacifica-upload-status
Epoch:		1
Version:	0.99.0
Release:	1%{?dist}
Summary:	The pacifica upload status web page
Group:		System Environment/Libraries
License:	GPLv2
URL:		http://www.example.com/
Source0:	%{name}-%{version}.tar.gz
BuildRoot:	%{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch:      noarch

BuildRequires:	rsync

%description

%prep
%setup -q

%build
rm -f system index.php
mv websystem/system system
mv websystem/index.php index.php
rm -rf websystem resources

%install
mkdir -p %{buildroot}/var/www/myemsl/status
rsync -r * %{buildroot}/var/www/myemsl/status/

%clean
rm -rf %{buildroot}

%files
%defattr(-,root,root,-)
/var/www/myemsl/status/*

%changelog
* Mon Mar 21 2016 David Brown <david.brown@pnnl.gov> 0.99.0-1
- Initial RHEL release.
