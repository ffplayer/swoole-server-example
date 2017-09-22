#/bin/bash
script_dir=$(cd $(dirname $0); pwd)

if [ $# != 2 ]
then
	echo "Usage: $0 sid port"
	exit 1
fi

sid=$1
port=$2

source_dir="${script_dir}/../"
release_dir="/media/sf_wwwroot/project-php/swoole_server/release/"
#release_dir="${script_dir}"

sid_dir="${release_dir}/${sid}"
project_dir="${sid_dir}/Sicbo"
port_project_dir="${project_dir}/${port}"

if [ ! -d ${sid_dir} ]
then
	echo ${sid_dir}
	exit
fi

if [ ! -d ${project_dir} ]
then
	mkdir -p ${project_dir}
fi

if [ ! -d ${port_project_dir} ]
then
	mkdir -p ${port_project_dir}
fi

rsync -av --delete --exclude=".svn" --exclude="Test/" "${source_dir}/src/" "${port_project_dir}/src/"
rsync -av --delete --exclude=".svn" --include="${sid}/***" --exclude="*" "${source_dir}/cfg/" "${port_project_dir}/cfg/"
rsync -av "${source_dir}/start.sh" "${port_project_dir}/start.sh"
rsync -av --delete --exclude=".svn" "${source_dir}/../include/" "${port_project_dir}/include/"
rsync -av --delete --exclude=".svn" --exclude="release.sh" "${script_dir}"  "${port_project_dir}"
